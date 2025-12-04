<?php

namespace App\Services;

use App\Models\StockSymbol;
use App\Models\FinancialData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Gerenciador de JSON para Agente Júlia
 * 
 * Processa e valida dados JSON retornados pelo Agente Júlia Python (AgentJulia.py)
 * que podem vir de yfinance ou de uma LLM. Normaliza os dados e salva no banco.
 */
class AgentJuliaJsonManager
{
    /**
     * Processa JSON retornado pelo Agente Júlia Python
     * 
     * @param string|array $jsonData JSON string ou array já decodificado
     * @param string|null $companyName Nome da empresa (opcional, pode vir do JSON)
     * @return array|null Dados processados ou null em caso de erro
     */
    public function processJson($jsonData, ?string $companyName = null): ?array
    {
        try {
            // Decodifica JSON se for string
            if (is_string($jsonData)) {
                $data = json_decode($jsonData, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Agent Julia JSON Manager: Erro ao decodificar JSON', [
                        'error' => json_last_error_msg(),
                        'json' => substr($jsonData, 0, 500) // Primeiros 500 caracteres para debug
                    ]);
                    return null;
                }
            } else {
                $data = $jsonData;
            }

            // Valida estrutura básica
            if (!$this->validateStructure($data)) {
                return null;
            }

            // Extrai informações principais
            $processed = $this->extractData($data, $companyName);

            // Valida dados extraídos
            if (!$this->validateData($processed)) {
                return null;
            }

            // Normaliza dados
            $normalized = $this->normalizeData($processed);

            Log::info('Agent Julia JSON Manager: Dados processados com sucesso', [
                'symbol' => $normalized['symbol'] ?? null,
                'company_name' => $normalized['company_name'] ?? null,
            ]);

            return $normalized;

        } catch (\Exception $e) {
            Log::error('Agent Julia JSON Manager: Erro ao processar JSON', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Salva dados processados no banco de dados
     * 
     * @param array $processedData Dados processados pelo processJson()
     * @return FinancialData|null Model criado ou null em caso de erro
     */
    public function saveToDatabase(array $processedData): ?FinancialData
    {
        try {
            // Busca ou cria StockSymbol
            $stockSymbol = $this->getOrCreateStockSymbol($processedData);

            if (!$stockSymbol) {
                Log::warning('Agent Julia JSON Manager: Não foi possível criar/encontrar StockSymbol', [
                    'symbol' => $processedData['symbol'] ?? null,
                    'company_name' => $processedData['company_name'] ?? null,
                ]);
                return null;
            }

            // Prepara dados para FinancialData
            $financialData = [
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $processedData['symbol'],
                'price' => $processedData['price'] ?? null,
                'previous_close' => $processedData['previous_close'] ?? null,
                'change' => $processedData['change'] ?? null,
                'change_percent' => $processedData['change_percent'] ?? null,
                'volume' => $processedData['volume'] ?? null,
                'market_cap' => $processedData['market_cap'] ?? null,
                'pe_ratio' => $processedData['pe_ratio'] ?? null,
                'dividend_yield' => $processedData['dividend_yield'] ?? null,
                'high_52w' => $processedData['high_52w'] ?? null,
                'low_52w' => $processedData['low_52w'] ?? null,
                'high' => $processedData['high'] ?? null,
                'low' => $processedData['low'] ?? null,
                'raw_data' => $processedData['raw_data'] ?? null,
                'source' => $processedData['source'] ?? 'agent_julia_python',
                'collected_at' => $processedData['collected_at'] ?? now(),
            ];

            // Remove campos null para não sobrescrever dados existentes
            $financialData = array_filter($financialData, function ($value) {
                return $value !== null;
            });

            // Cria ou atualiza FinancialData
            $financial = FinancialData::updateOrCreate(
                [
                    'stock_symbol_id' => $stockSymbol->id,
                    'symbol' => $processedData['symbol'],
                    // Usa collected_at como chave adicional para evitar duplicatas muito próximas
                ],
                $financialData
            );

            Log::info('Agent Julia JSON Manager: Dados salvos no banco', [
                'financial_data_id' => $financial->id,
                'symbol' => $financial->symbol,
            ]);

            return $financial;

        } catch (\Exception $e) {
            Log::error('Agent Julia JSON Manager: Erro ao salvar no banco', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Valida estrutura básica do JSON
     * 
     * @param array $data
     * @return bool
     */
    protected function validateStructure(array $data): bool
    {
        // Verifica se tem pelo menos symbol ou company_name
        if (!isset($data['symbol']) && !isset($data['company_name'])) {
            Log::warning('Agent Julia JSON Manager: JSON sem symbol ou company_name', [
                'keys' => array_keys($data)
            ]);
            return false;
        }

        // Verifica se não é um erro
        if (isset($data['error'])) {
            Log::warning('Agent Julia JSON Manager: JSON contém erro', [
                'error' => $data['error']
            ]);
            return false;
        }

        return true;
    }

    /**
     * Extrai dados do JSON estruturado
     * 
     * @param array $data
     * @param string|null $companyName
     * @return array
     */
    protected function extractData(array $data, ?string $companyName = null): array
    {
        $extracted = [
            'symbol' => $data['symbol'] ?? null,
            'company_name' => $companyName ?? $data['company_name'] ?? $data['searched_name'] ?? null,
            'searched_name' => $data['searched_name'] ?? $companyName,
            
            // Dados de preço e mercado
            'price' => $this->extractNumeric($data['price'] ?? null),
            'previous_close' => $this->extractNumeric($data['previous_close'] ?? null),
            'change' => $this->extractNumeric($data['change'] ?? null),
            'change_percent' => $this->extractNumeric($data['change_percent'] ?? null),
            'volume' => $this->extractNumeric($data['volume'] ?? null),
            'market_cap' => $this->extractNumeric($data['market_cap'] ?? null),
            'high_52w' => $this->extractNumeric($data['high_52w'] ?? null),
            'low_52w' => $this->extractNumeric($data['low_52w'] ?? null),
            'high' => $this->extractNumeric($data['high'] ?? null),
            'low' => $this->extractNumeric($data['low'] ?? null),
            
            // Indicadores de avaliação
            'pe_ratio' => $this->extractNumeric($data['pe_ratio'] ?? null),
            'price_to_book' => $this->extractNumeric($data['price_to_book'] ?? null),
            'peg_ratio' => $this->extractNumeric($data['peg_ratio'] ?? null),
            'enterprise_value' => $this->extractNumeric($data['enterprise_value'] ?? null),
            'enterprise_to_revenue' => $this->extractNumeric($data['enterprise_to_revenue'] ?? null),
            'enterprise_to_ebitda' => $this->extractNumeric($data['enterprise_to_ebitda'] ?? null),
            
            // Dividendos
            'dividend_yield' => $this->extractNumeric($data['dividend_yield'] ?? $data['dividend_info']['dividend_yield'] ?? null),
            'dividend_rate' => $this->extractNumeric($data['dividend_rate'] ?? $data['dividend_info']['dividend_rate'] ?? null),
            
            // Informações da empresa (aninhadas)
            'company_info' => $data['company_info'] ?? [],
            'financial_metrics' => $data['financial_metrics'] ?? [],
            'dividend_info' => $data['dividend_info'] ?? [],
            'growth_metrics' => $data['growth_metrics'] ?? [],
            
            // Metadados
            'currency' => $data['currency'] ?? 'BRL',
            'exchange' => $data['exchange'] ?? 'SAO',
            'raw_data' => $data['raw_data'] ?? $data, // Mantém dados completos
            'collected_at' => $this->parseDate($data['collected_at'] ?? null),
            'source' => $data['source'] ?? 'agent_julia_python',
        ];

        // Calcula change e change_percent se não estiverem presentes
        if ($extracted['change'] === null && $extracted['price'] !== null && $extracted['previous_close'] !== null) {
            $extracted['change'] = $extracted['price'] - $extracted['previous_close'];
        }

        if ($extracted['change_percent'] === null && $extracted['change'] !== null && $extracted['previous_close'] !== null && $extracted['previous_close'] > 0) {
            $extracted['change_percent'] = ($extracted['change'] / $extracted['previous_close']) * 100;
        }

        return $extracted;
    }

    /**
     * Valida dados extraídos
     * 
     * @param array $data
     * @return bool
     */
    protected function validateData(array $data): bool
    {
        $validator = Validator::make($data, [
            'symbol' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            Log::warning('Agent Julia JSON Manager: Validação falhou', [
                'errors' => $validator->errors()->toArray()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Normaliza dados para formato padrão
     * 
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        // Normaliza symbol (remove .SA se necessário, adiciona se for brasileiro)
        if (isset($data['symbol'])) {
            $symbol = strtoupper(trim($data['symbol']));
            
            // Se não tem .SA e parece ser brasileiro (exchange SAO), adiciona
            if (strpos($symbol, '.') === false && ($data['exchange'] ?? 'SAO') === 'SAO') {
                // Remove .SA se já tiver
                $symbol = str_replace('.SA', '', $symbol);
                // Adiciona .SA se for ticker curto (até 6 caracteres)
                if (strlen($symbol) <= 6 && strpos($symbol, '.') === false) {
                    $symbol = $symbol . '.SA';
                }
            }
            
            $data['symbol'] = $symbol;
        }

        // Garante que company_name está presente
        if (empty($data['company_name']) && !empty($data['company_info']['name'])) {
            $data['company_name'] = $data['company_info']['name'];
        }

        return $data;
    }

    /**
     * Busca ou cria StockSymbol
     * 
     * @param array $processedData
     * @return StockSymbol|null
     */
    protected function getOrCreateStockSymbol(array $processedData): ?StockSymbol
    {
        try {
            $symbol = $processedData['symbol'];
            $companyName = $processedData['company_name'] ?? $symbol;

            // Busca por symbol
            $stockSymbol = StockSymbol::where('symbol', $symbol)->first();

            if (!$stockSymbol) {
                // Cria novo StockSymbol
                $stockSymbol = StockSymbol::create([
                    'symbol' => $symbol,
                    'company_name' => $companyName,
                    'is_active' => true,
                    'is_default' => false,
                ]);

                Log::info('Agent Julia JSON Manager: StockSymbol criado', [
                    'id' => $stockSymbol->id,
                    'symbol' => $symbol,
                ]);
            } else {
                // Atualiza company_name se necessário
                if (empty($stockSymbol->company_name) && !empty($companyName)) {
                    $stockSymbol->update(['company_name' => $companyName]);
                }
            }

            return $stockSymbol;

        } catch (\Exception $e) {
            Log::error('Agent Julia JSON Manager: Erro ao buscar/criar StockSymbol', [
                'error' => $e->getMessage(),
                'symbol' => $processedData['symbol'] ?? null,
            ]);
            return null;
        }
    }

    /**
     * Extrai valor numérico de string ou número
     * 
     * @param mixed $value
     * @return float|null
     */
    protected function extractNumeric($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove caracteres não numéricos (exceto ponto, vírgula e sinal negativo)
            $cleaned = preg_replace('/[^0-9.,\-]/', '', $value);
            $cleaned = str_replace(',', '.', $cleaned);
            
            return is_numeric($cleaned) ? (float) $cleaned : null;
        }

        return null;
    }

    /**
     * Parse data de string para Carbon
     * 
     * @param string|null $dateString
     * @return \Carbon\Carbon|null
     */
    protected function parseDate(?string $dateString)
    {
        if (empty($dateString)) {
            return now();
        }

        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning('Agent Julia JSON Manager: Erro ao parsear data', [
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);
            return now();
        }
    }

    /**
     * Processa e salva JSON em uma única operação
     * 
     * @param string|array $jsonData
     * @param string|null $companyName
     * @return FinancialData|null
     */
    public function processAndSave($jsonData, ?string $companyName = null): ?FinancialData
    {
        $processed = $this->processJson($jsonData, $companyName);
        
        if (!$processed) {
            return null;
        }

        return $this->saveToDatabase($processed);
    }
}

