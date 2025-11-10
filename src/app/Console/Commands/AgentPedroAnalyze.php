<?php

namespace App\Console\Commands;

use App\Models\StockSymbol;
use App\Models\SentimentAnalysis;
use App\Services\NewsAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Agente Pedro: Análise de mídia e sentimento
 * 
 * Responsabilidade: Analisar o que o mercado e a mídia estão dizendo sobre a empresa
 * (sentimento, trending, notícias relevantes)
 * 
 * Este comando analisa sentimentos de mercado, notícias e tendências relacionadas às ações.
 */
class AgentPedroAnalyze extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:pedro:analyze 
                            {--symbol= : Símbolo da ação a ser analisada (opcional)}
                            {--all : Analisar todas as ações monitoradas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agente Pedro: Analisa sentimento de mercado e mídia sobre as ações';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Agente Pedro iniciando análise de sentimento e mídia...');

        try {
            $symbol = $this->option('symbol');
            $all = $this->option('all');
            $service = new NewsAnalysisService();
            $analyzedCount = 0;
            $errorCount = 0;

            // Determina quais símbolos analisar
            $symbolsToAnalyze = $this->getSymbolsToAnalyze($symbol, $all);

            if (empty($symbolsToAnalyze)) {
                $this->warn('⚠️ Nenhuma ação encontrada para análise.');
                return Command::SUCCESS;
            }

            $this->info("📰 Analisando sentimento de " . count($symbolsToAnalyze) . " ação(ões)...");

            $bar = $this->output->createProgressBar(count($symbolsToAnalyze));
            $bar->start();

            foreach ($symbolsToAnalyze as $stockSymbol) {
                try {
                    // Busca notícias sobre a ação/empresa
                    $articles = $service->searchNews(
                        $stockSymbol->symbol,
                        $stockSymbol->company_name,
                        20
                    );

                    // Analisa sentimento das notícias
                    $analysis = $service->analyzeSentiment($articles);

                    // Salva análise no banco de dados
                    SentimentAnalysis::create([
                        'stock_symbol_id' => $stockSymbol->id,
                        'symbol' => $stockSymbol->symbol,
                        'sentiment' => $analysis['sentiment'],
                        'sentiment_score' => $analysis['sentiment_score'],
                        'news_count' => $analysis['news_count'],
                        'positive_count' => $analysis['positive_count'],
                        'negative_count' => $analysis['negative_count'],
                        'neutral_count' => $analysis['neutral_count'],
                        'trending_topics' => $analysis['trending_topics'],
                        'news_sources' => $analysis['news_sources'],
                        'raw_data' => $analysis['raw_data'],
                        'source' => 'news_api',
                        'analyzed_at' => now(),
                    ]);

                    $analyzedCount++;
                    $this->line("  ✓ {$stockSymbol->symbol}: {$analysis['sentiment']} (score: {$analysis['sentiment_score']})");

                    Log::info('Agent Pedro: Análise concluída', [
                        'symbol' => $stockSymbol->symbol,
                        'sentiment' => $analysis['sentiment'],
                        'score' => $analysis['sentiment_score'],
                        'news_count' => $analysis['news_count'],
                    ]);
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Agent Pedro: Erro ao analisar símbolo', [
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

            $this->info("✅ Análise concluída! {$analyzedCount} ação(ões) analisada(s) com sucesso.");
            if ($errorCount > 0) {
                $this->warn("⚠️ {$errorCount} erro(s) durante a análise.");
            }

            Log::info('Agent Pedro: Análise de sentimento concluída', [
                'analyzed' => $analyzedCount,
                'errors' => $errorCount,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro ao analisar sentimento: ' . $e->getMessage());
            Log::error('Agent Pedro: Erro ao analisar sentimento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Obtém símbolos para análise baseado nas opções
     * 
     * @param string|null $symbol
     * @param bool $all
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getSymbolsToAnalyze(?string $symbol, bool $all)
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

