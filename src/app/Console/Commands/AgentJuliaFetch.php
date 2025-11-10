<?php

namespace App\Console\Commands;

use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Services\YahooFinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Agente Júlia: Coleta dados financeiros atualizados
 * 
 * Responsabilidade: Coletar dados financeiros de mercado usando OpenAI API
 * 
 * Este comando deve ser executado frequentemente para manter os dados atualizados.
 * Usa OpenAI para buscar e analisar informações financeiras sobre ações.
 * 
 * Nota: OpenAI pode não ter dados atualizados em tempo real. Para produção,
 * considere usar APIs financeiras especializadas ou combinar com outras fontes.
 */
class AgentJuliaFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:julia:fetch 
                            {--symbol= : Símbolo da ação a ser analisada (opcional)}
                            {--all : Coletar dados de todas as ações monitoradas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agente Júlia: Coleta dados financeiros atualizados de mercado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Agente Júlia iniciando coleta de dados financeiros...');

        try {
            $symbol = $this->option('symbol');
            $all = $this->option('all');
            $service = new YahooFinanceService();
            $collectedCount = 0;
            $errorCount = 0;

            // Determina quais símbolos coletar
            $symbolsToCollect = $this->getSymbolsToCollect($symbol, $all);

            if (empty($symbolsToCollect)) {
                $this->warn('⚠️ Nenhuma ação encontrada para coleta.');
                return Command::SUCCESS;
            }

            $this->info("📊 Coletando dados de " . count($symbolsToCollect) . " ação(ões)...");

            $bar = $this->output->createProgressBar(count($symbolsToCollect));
            $bar->start();

            foreach ($symbolsToCollect as $stockSymbol) {
                try {
                    $quote = $service->getQuote($stockSymbol->symbol);

                    if ($quote) {
                        // Salva dados financeiros no banco
                        FinancialData::create([
                            'stock_symbol_id' => $stockSymbol->id,
                            'symbol' => $stockSymbol->symbol,
                            'price' => $quote['price'],
                            'previous_close' => $quote['previous_close'],
                            'change' => $quote['change'],
                            'change_percent' => $quote['change_percent'],
                            'volume' => $quote['volume'],
                            'market_cap' => $quote['market_cap'],
                            'pe_ratio' => $quote['pe_ratio'],
                            'dividend_yield' => $quote['dividend_yield'],
                            'high_52w' => $quote['high_52w'],
                            'low_52w' => $quote['low_52w'],
                            'raw_data' => $quote['raw_data'],
                            'source' => $quote['source'] ?? 'yahoo_finance',
                            'collected_at' => $quote['collected_at'] ?? now(),
                        ]);

                        $collectedCount++;
                        Log::info('Agent Julia: Dados coletados', [
                            'symbol' => $stockSymbol->symbol,
                            'price' => $quote['price'],
                        ]);
                    } else {
                        $errorCount++;
                        Log::warning('Agent Julia: Falha ao coletar dados', [
                            'symbol' => $stockSymbol->symbol,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Agent Julia: Erro ao coletar símbolo', [
                        'symbol' => $stockSymbol->symbol,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
                
                // Pequeno delay para não sobrecarregar a API
                usleep(500000); // 0.5 segundos
            }

            $bar->finish();
            $this->newLine();

            $this->info("✅ Coleta concluída! {$collectedCount} ação(ões) coletada(s) com sucesso.");
            if ($errorCount > 0) {
                $this->warn("⚠️ {$errorCount} erro(s) durante a coleta.");
            }

            Log::info('Agent Julia: Coleta de dados financeiros concluída', [
                'collected' => $collectedCount,
                'errors' => $errorCount,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao coletar dados: ' . $e->getMessage());
            Log::error('Agent Julia: Erro ao coletar dados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Obtém símbolos para coleta baseado nas opções
     * 
     * @param string|null $symbol
     * @param bool $all
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getSymbolsToCollect(?string $symbol, bool $all)
    {
        if ($all) {
            return StockSymbol::active()->get();
        } elseif ($symbol) {
            return StockSymbol::where('symbol', $symbol)->where('is_active', true)->get();
        } else {
            return StockSymbol::active()->default()->get();
        }
    }
}

