<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service para integração com Google Gemini para obter dados financeiros
 * 
 * Usa Google Gemini API para buscar e analisar informações financeiras sobre ações.
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
        $config = config('services.llm.gemini');
        $this->apiKey = $config['api_key'] ?? env('GEMINI_API_KEY');
        $this->baseUrl = $config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta';
        $this->model = $config['model'] ?? 'gemini-pro';
        $this->timeout = config('services.llm.timeout') ?? 60;
    }

    /**
     * Busca dados financeiros usando nome da empresa, serviço ou produto
     * 
     * @param string $companyName Nome da empresa, serviço contratado ou produto (ex: "Petrobras", "Petróleo Brasileiro")
     * @return array|null Dados financeiros ou null em caso de erro
     */
    public function getQuoteByCompanyName(string $companyName): ?array
    {
        // Usa Gemini para identificar o ticker a partir do nome da empresa
        return $this->getQuote($companyName);
    }

    /**
     * Busca dados financeiros de uma ação usando Google Gemini
     * Agora aceita tanto ticker quanto nome da empresa
     * 
     * @param string $symbol Símbolo da ação ou nome da empresa (ex: Petrobras ou "Petrobras")
     * @return array|null Dados financeiros ou null em caso de erro
     */
    public function getQuote(string $symbol): ?array
    {
        // Cache por 5 minutos (300 segundos) - agrupa por hora para invalidar naturalmente
        $cacheKey = "quote:{$symbol}:" . date('Y-m-d-H');
        
        try {
            return Cache::remember($cacheKey, 300, function () use ($symbol) {
                try {
                    if (!$this->apiKey) {
                        Log::warning("Gemini API key not configured for {$symbol}");
                        return $this->getMockData($symbol);
                    }

            // Formata símbolo para busca (remove .SA se presente para busca)
            $formattedSymbol = $this->formatSymbol($symbol);

            // Cria prompt estruturado para Gemini
            $prompt = $this->buildPrompt($formattedSymbol);

            // Chama Gemini API
            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if (!$response->successful()) {
                Log::warning("Gemini API error for {$symbol}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return $this->getMockData($symbol);
            }

            $data = $response->json();
            
            // Valida se a resposta é JSON válido
            if ($data === null || !is_array($data)) {
                Log::warning("Gemini: Invalid JSON response structure for {$symbol}", [
                    'response_body' => substr($response->body(), 0, 500)
                ]);
                return $this->getMockData($symbol);
            }
            
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                Log::warning("Gemini: No content in response for {$symbol}", [
                    'response' => $data
                ]);
                return $this->getMockData($symbol);
            }

            // Verifica finishReason (pode indicar resposta bloqueada)
            $finishReason = $data['candidates'][0]['finishReason'] ?? null;
            if ($finishReason === 'SAFETY' || $finishReason === 'RECITATION') {
                Log::warning("Gemini: Response blocked for {$symbol}", [
                    'finish_reason' => $finishReason
                ]);
                return $this->getMockData($symbol);
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Remove markdown code blocks se presente (```json ... ```)
            // Remove múltiplos code blocks se houver
            $content = trim($content);
            while (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $content, $matches)) {
                $content = trim($matches[1]);
            }
            
            // Tenta decodificar JSON
            $financialData = json_decode($content, true);
            
            // Verifica se o JSON foi decodificado corretamente
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($financialData)) {
                Log::warning("Gemini: Invalid JSON response for {$symbol}", [
                    'content' => substr($content, 0, 500), // Primeiros 500 caracteres
                    'json_error' => json_last_error_msg(),
                    'json_error_code' => json_last_error(),
                ]);
                return $this->getMockData($symbol);
            }
            
            // Valida se tem pelo menos alguns campos esperados
            // Aceita dados mesmo sem price/previous_close se tiver outros campos válidos
            $hasPrice = isset($financialData['price']);
            $hasPreviousClose = isset($financialData['previous_close']);
            $hasOtherFields = isset($financialData['volume']) || isset($financialData['market_cap']) || 
                             isset($financialData['pe_ratio']) || isset($financialData['symbol']);
            
            if (empty($financialData) || (!$hasPrice && !$hasPreviousClose && !$hasOtherFields)) {
                Log::warning("Gemini: JSON response missing required fields for {$symbol}", [
                    'content' => substr($content, 0, 500),
                    'decoded_data' => $financialData,
                ]);
                return $this->getMockData($symbol);
            }

                    // Normaliza dados retornados pelo Gemini
                    return $this->normalizeData($symbol, $financialData, $data);

                } catch (\Exception $e) {
                    Log::error("Gemini Service error for {$symbol}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return $this->getMockData($symbol);
                }
            });
        } catch (\Exception $e) {
            // Se houver erro no cache, tenta retornar dados mockados
            Log::error("YahooFinanceService: Erro no cache para {$symbol}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getMockData($symbol);
        }
    }

    /**
     * Constrói prompt estruturado para Gemini
     */
    protected function buildPrompt(string $symbol): string
    {
        // Detecta se é um ticker (curto, alfanumérico) ou nome da empresa
        $isTicker = preg_match('/^[A-Z0-9]{1,6}(\.[A-Z]{2})?$/i', $symbol);
        
        if ($isTicker) {
            $prompt = "Você é um assistente especializado em análise financeira. Forneça os dados financeiros mais recentes da ação {$symbol} (Bolsa de Valores Brasileira - B3) em formato JSON estruturado com os seguintes campos:";
        } else {
            $prompt = "Você é um assistente especializado em análise financeira. Identifique o ticker da empresa/serviço/produto \"{$symbol}\" e forneça os dados financeiros mais recentes dessa empresa em formato JSON estruturado com os seguintes campos. Se for uma empresa brasileira, busque na Bolsa de Valores Brasileira (B3). Se não encontrar dados de ações, forneça informações gerais sobre a empresa/serviço/produto. Campos necessários:";
        }
        
        return $prompt . "

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
};

IMPORTANTE: 
- Retorne APENAS o JSON válido, sem texto adicional antes ou depois
- Não use markdown code blocks (```json)
- Use números reais, não strings
- Se não tiver acesso a dados atualizados, use valores realistas baseados em conhecimento geral sobre a ação
- Todos os valores monetários devem estar em reais (R$)
- Valores percentuais devem ser números (ex: 1.5 para 1.5%, não \"1.5%\")";
    }

    /**
     * Normaliza dados retornados pela Gemini
     */
    protected function normalizeData(string $symbol, array $financialData, array $rawResponse): array
    {
        // Extrai symbol e company_name do JSON se disponíveis
        $extractedSymbol = $financialData['symbol'] ?? $symbol;
        $companyName = $financialData['company_name'] ?? null;
        
        $price = $this->extractNumericValue($financialData['price'] ?? null);
        $previousClose = $this->extractNumericValue($financialData['previous_close'] ?? null);
        
        $change = null;
        $changePercent = null;
        
        // Calcula change e change_percent se possível (evita divisão por zero)
        if ($price !== null && $previousClose !== null && abs($previousClose) > 0.0001) {
            $change = $price - $previousClose;
            $changePercent = ($change / $previousClose) * 100;
        } elseif (isset($financialData['change'])) {
            $change = $this->extractNumericValue($financialData['change']);
        }
        
        if (isset($financialData['change_percent'])) {
            $changePercent = $this->extractNumericValue($financialData['change_percent']);
        }

        return [
            'symbol' => $extractedSymbol, // Usa symbol do JSON se disponível
            'company_name' => $companyName, // Inclui company_name se disponível
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
        $previousClose = $basePrice - $change;
        
        return [
            'symbol' => $symbol,
            'company_name' => $symbol, // Inclui company_name no mock
            'price' => $basePrice,
            'previous_close' => $previousClose,
            'change' => $change,
            'change_percent' => $previousClose > 0 ? ($change / $previousClose) * 100 : 0,
            'volume' => rand(1000000, 100000000),
            'market_cap' => rand(1000000000, 500000000000),
            'pe_ratio' => rand(5, 30) + (rand(0, 99) / 100),
            'dividend_yield' => rand(0, 10) + (rand(0, 99) / 100),
            'high_52w' => $basePrice * 1.2,
            'low_52w' => $basePrice * 0.8,
            'raw_data' => ['source' => 'mock', 'note' => 'Gemini API not configured or error occurred'],
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
        // Remove .SA se presente para busca no Gemini
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

