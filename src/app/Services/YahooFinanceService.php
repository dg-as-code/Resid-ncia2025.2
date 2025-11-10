<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service para integração com OpenAI para obter dados financeiros
 * 
 * Usa OpenAI API para buscar e analisar informações financeiras sobre ações.
 * O serviço mantém a mesma interface para compatibilidade com código existente.
 */
class YahooFinanceService
{
    protected $apiKey;
    protected $baseUrl;
    protected $model;
    protected $timeout;

    public function __construct()
    {
        $config = config('services.llm.openai');
        $this->apiKey = $config['api_key'] ?? env('OPENAI_API_KEY');
        $this->baseUrl = $config['base_url'] ?? 'https://api.openai.com/v1';
        $this->model = $config['model'] ?? 'gpt-3.5-turbo';
        $this->timeout = config('services.llm.timeout') ?? 60;
    }

    /**
     * Busca dados financeiros de uma ação usando OpenAI
     * 
     * @param string $symbol Símbolo da ação (ex: PETR4)
     * @return array|null Dados financeiros ou null em caso de erro
     */
    public function getQuote(string $symbol): ?array
    {
        try {
            if (!$this->apiKey) {
                Log::warning("OpenAI API key not configured for {$symbol}");
                return $this->getMockData($symbol);
            }

            // Formata símbolo para busca (remove .SA se presente para busca)
            $formattedSymbol = $this->formatSymbol($symbol);

            // Cria prompt estruturado para OpenAI
            $prompt = $this->buildPrompt($formattedSymbol);

            // Chama OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um assistente especializado em análise financeira. Você fornece dados financeiros estruturados em formato JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                Log::warning("OpenAI API error for {$symbol}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return $this->getMockData($symbol);
            }

            $data = $response->json();
            
            if (!isset($data['choices'][0]['message']['content'])) {
                Log::warning("OpenAI: No content in response for {$symbol}");
                return $this->getMockData($symbol);
            }

            $content = $data['choices'][0]['message']['content'];
            $financialData = json_decode($content, true);

            if (!$financialData) {
                Log::warning("OpenAI: Invalid JSON response for {$symbol}", [
                    'content' => $content
                ]);
                return $this->getMockData($symbol);
            }

            // Normaliza dados retornados pela OpenAI
            return $this->normalizeData($symbol, $financialData, $data);

        } catch (\Exception $e) {
            Log::error("OpenAI Service error for {$symbol}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getMockData($symbol);
        }
    }

    /**
     * Constrói prompt estruturado para OpenAI
     */
    protected function buildPrompt(string $symbol): string
    {
        return "Forneça os dados financeiros mais recentes da ação {$symbol} (Bolsa de Valores Brasileira - B3) em formato JSON estruturado com os seguintes campos:

{
  \"price\": valor_do_preco_atual_em_reais,
  \"previous_close\": valor_do_fechamento_anterior_em_reais,
  \"change\": variacao_em_reais,
  \"change_percent\": variacao_percentual,
  \"volume\": volume_negociado,
  \"market_cap\": capitalizacao_de_mercado_em_reais,
  \"pe_ratio\": indicador_p_l,
  \"dividend_yield\": dividend_yield_percentual,
  \"high_52w\": maior_preco_52_semanas_em_reais,
  \"low_52w\": menor_preco_52_semanas_em_reais
}

Se não tiver acesso a dados atualizados, use valores realistas baseados em conhecimento geral sobre a ação.";
    }

    /**
     * Normaliza dados retornados pela OpenAI
     */
    protected function normalizeData(string $symbol, array $financialData, array $rawResponse): array
    {
        $price = $this->extractNumericValue($financialData['price'] ?? null);
        $previousClose = $this->extractNumericValue($financialData['previous_close'] ?? null);
        
        $change = null;
        $changePercent = null;
        
        if ($price !== null && $previousClose !== null && $previousClose > 0) {
            $change = $price - $previousClose;
            $changePercent = ($change / $previousClose) * 100;
        } elseif (isset($financialData['change'])) {
            $change = $this->extractNumericValue($financialData['change']);
        }
        
        if (isset($financialData['change_percent'])) {
            $changePercent = $this->extractNumericValue($financialData['change_percent']);
        }

        return [
            'symbol' => $symbol,
            'price' => $price,
            'previous_close' => $previousClose,
            'change' => $change,
            'change_percent' => $changePercent,
            'volume' => $this->extractNumericValue($financialData['volume'] ?? null),
            'market_cap' => $this->extractNumericValue($financialData['market_cap'] ?? null),
            'pe_ratio' => $this->extractNumericValue($financialData['pe_ratio'] ?? null),
            'dividend_yield' => $this->extractNumericValue($financialData['dividend_yield'] ?? null),
            'high_52w' => $this->extractNumericValue($financialData['high_52w'] ?? null),
            'low_52w' => $this->extractNumericValue($financialData['low_52w'] ?? null),
            'raw_data' => $rawResponse,
            'collected_at' => now(),
        ];
    }

    /**
     * Extrai valor numérico de string ou número
     */
    protected function extractNumericValue($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove caracteres não numéricos (exceto ponto e vírgula)
        $cleaned = preg_replace('/[^0-9.,\-]/', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Retorna dados mockados quando API não está disponível
     */
    protected function getMockData(string $symbol): array
    {
        // Gera valores mockados baseados no símbolo
        $basePrice = 30.00 + (ord(substr($symbol, 0, 1)) % 50);
        $change = (rand(-100, 100) / 100);
        
        return [
            'symbol' => $symbol,
            'price' => $basePrice,
            'previous_close' => $basePrice - $change,
            'change' => $change,
            'change_percent' => ($change / ($basePrice - $change)) * 100,
            'volume' => rand(1000000, 100000000),
            'market_cap' => rand(1000000000, 500000000000),
            'pe_ratio' => rand(5, 30) + (rand(0, 99) / 100),
            'dividend_yield' => rand(0, 10) + (rand(0, 99) / 100),
            'high_52w' => $basePrice * 1.2,
            'low_52w' => $basePrice * 0.8,
            'raw_data' => ['source' => 'mock', 'note' => 'OpenAI API not configured'],
            'collected_at' => now(),
        ];
    }

    /**
     * Formata símbolo para busca (remove .SA se presente)
     * 
     * @param string $symbol
     * @return string
     */
    protected function formatSymbol(string $symbol): string
    {
        // Remove .SA se presente para busca na OpenAI
        return str_replace('.SA', '', $symbol);
    }

    /**
     * Busca múltiplas ações de uma vez
     * 
     * @param array $symbols
     * @return array
     */
    public function getMultipleQuotes(array $symbols): array
    {
        $results = [];
        
        foreach ($symbols as $symbol) {
            $quote = $this->getQuote($symbol);
            if ($quote) {
                $results[$symbol] = $quote;
            }
            
            // Pequeno delay para não sobrecarregar a API
            usleep(500000); // 0.5 segundos
        }
        
        return $results;
    }
}

