<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Services\YahooFinanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Agente Júlia - Coleta de Dados Financeiros
 * 
 * FLUXO DOS AGENTES:
 * Este job representa o Agente Júlia, responsável por coletar dados financeiros
 * atualizados de mercado para uma empresa/ação.
 * 
 * Fluxo:
 * 1. Busca dados financeiros via YahooFinanceService (usando Gemini API)
 * 2. Cria ou atualiza StockSymbol
 * 3. Salva FinancialData no banco
 * 4. Atualiza Analysis com financial_data_id
 * 
 * Próximo passo: AnalyzeMarketSentimentJob (Agente Pedro)
 * 
 * Dados coletados:
 * - Preço atual e fechamento anterior
 * - Variação e percentual
 * - Volume negociado
 * - Capitalização de mercado
 * - Indicadores (P/L, Dividend Yield)
 * - Máximas e mínimas de 52 semanas
 * - Nome da empresa (company_name)
 * - Dados brutos (raw_data)
 */
class FetchFinancialDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $analysis;
    protected $companyName;

    /**
     * Create a new job instance.
     * 
     * @param Analysis $analysis
     * @param string|null $companyName Nome da empresa (opcional, usa analysis->company_name se não fornecido)
     */
    public function __construct(Analysis $analysis, ?string $companyName = null)
    {
        $this->analysis = $analysis;
        $this->companyName = $companyName ?? $analysis->company_name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Atualiza status para fetching_financial_data
            $this->analysis->update([
                'status' => 'fetching_financial_data',
                'started_at' => now(),
            ]);
            
            // Atualiza status após coleta bem-sucedida
            $this->analysis->update([
                'status' => 'analyzing_sentiment',
            ]);

            // Chama serviço para buscar dados financeiros usando nome da empresa
            $service = new YahooFinanceService();
            $quote = $service->getQuoteByCompanyName($this->companyName);

            if (!$quote) {
                throw new \Exception("Não foi possível obter dados financeiros para \"{$this->companyName}\"");
            }

            // Extrai ticker dos dados retornados
            $ticker = $quote['symbol'] ?? $this->analysis->ticker ?? strtoupper(substr($this->companyName, 0, 4));
            $companyNameFromData = $quote['company_name'] ?? $this->companyName;

            // Busca ou cria StockSymbol usando o ticker encontrado
            $stockSymbol = StockSymbol::firstOrCreate(
                ['symbol' => $ticker],
                [
                    'company_name' => $companyNameFromData,
                    'is_active' => true,
                ]
            );

            // Atualiza company_name se necessário
            if ($stockSymbol->company_name !== $companyNameFromData) {
                $stockSymbol->update(['company_name' => $companyNameFromData]);
            }

            $this->analysis->update([
                'stock_symbol_id' => $stockSymbol->id,
                'ticker' => $ticker,
            ]);

            // Salva dados financeiros completos (alinhado com executeJulia do OrchestrationController)
            $financialData = FinancialData::create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $ticker,
                'price' => $quote['price'] ?? null,
                'previous_close' => $quote['previous_close'] ?? null,
                'change' => $quote['change'] ?? null,
                'change_percent' => $quote['change_percent'] ?? null,
                'volume' => $quote['volume'] ?? null,
                'market_cap' => $quote['market_cap'] ?? null,
                'pe_ratio' => $quote['pe_ratio'] ?? null,
                'dividend_yield' => $quote['dividend_yield'] ?? null,
                'high_52w' => $quote['high_52w'] ?? null,
                'low_52w' => $quote['low_52w'] ?? null,
                'raw_data' => $quote['raw_data'] ?? null, // Dados brutos para uso pelo Agente Pedro
                'source' => 'gemini',
                'collected_at' => $quote['collected_at'] ?? now(),
            ]);

            // Atualiza análise com financial_data_id
            $this->analysis->update(['financial_data_id' => $financialData->id]);

            Log::info('FetchFinancialDataJob: Dados financeiros coletados', [
                'analysis_id' => $this->analysis->id,
                'company_name' => $this->companyName,
                'ticker' => $ticker,
            ]);

        } catch (\Exception $e) {
            Log::error('FetchFinancialDataJob: Erro ao coletar dados financeiros', [
                'analysis_id' => $this->analysis->id,
                'company_name' => $this->companyName,
                'error' => $e->getMessage(),
            ]);

            $this->analysis->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}

