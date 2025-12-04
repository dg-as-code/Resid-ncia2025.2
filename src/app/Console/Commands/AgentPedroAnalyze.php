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
        $this->info(' Agente Pedro iniciando análise de sentimento e mídia...');

        try {
            $symbol = $this->option('symbol');
            $all = $this->option('all');
            $service = new NewsAnalysisService();
            $analyzedCount = 0;
            $errorCount = 0;

            // Determina quais símbolos analisar
            $symbolsToAnalyze = $this->getSymbolsToAnalyze($symbol, $all);

            if (empty($symbolsToAnalyze)) {
                $this->warn(' Nenhuma ação encontrada para análise.');
                return Command::SUCCESS;
            }

            $this->info(" Analisando sentimento de " . count($symbolsToAnalyze) . " ação(ões)...");

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

                    // Analisa sentimento das notícias com LLM (análise enriquecida)
                    // Busca dados financeiros recentes para contexto (otimizado: eager loading)
                    $latestFinancial = $stockSymbol->latestFinancialData;
                    $financialData = $latestFinancial ? [
                        'price' => $latestFinancial->price,
                        'change_percent' => $latestFinancial->change_percent,
                        'volume' => $latestFinancial->volume,
                        'market_cap' => $latestFinancial->market_cap,
                        'pe_ratio' => $latestFinancial->pe_ratio,
                        'dividend_yield' => $latestFinancial->dividend_yield,
                        'high_52w' => $latestFinancial->high_52w,
                        'low_52w' => $latestFinancial->low_52w,
                    ] : [];
                    
                    $analysis = $service->analyzeSentiment(
                        $articles,
                        $stockSymbol->symbol,
                        $stockSymbol->company_name,
                        $financialData
                    );

                    // Prepara dados para salvar (campos obrigatórios primeiro)
                    $sentimentData = [
                        'stock_symbol_id' => $stockSymbol->id,
                        'symbol' => $stockSymbol->symbol,
                        'sentiment' => $analysis['sentiment'],
                        'sentiment_score' => $analysis['sentiment_score'],
                        'news_count' => $analysis['news_count'],
                        'positive_count' => $analysis['positive_count'] ?? 0,
                        'negative_count' => $analysis['negative_count'] ?? 0,
                        'neutral_count' => $analysis['neutral_count'] ?? 0,
                        'trending_topics' => is_array($analysis['trending_topics'] ?? null) 
                            ? implode(', ', $analysis['trending_topics']) 
                            : ($analysis['trending_topics'] ?? null),
                        'news_sources' => $analysis['news_sources'] ?? null,
                        'raw_data' => $analysis['raw_data'] ?? null,
                        'source' => 'news_api_llm', // Indica que foi enriquecido com LLM
                        'analyzed_at' => now(),
                    ];
                    
                    // Adiciona campos de análise de mercado e macroeconomia (se disponíveis)
                    if (isset($analysis['market_analysis'])) {
                        $sentimentData['market_analysis'] = $analysis['market_analysis'];
                    }
                    if (isset($analysis['macroeconomic_analysis'])) {
                        $sentimentData['macroeconomic_analysis'] = $analysis['macroeconomic_analysis'];
                    }
                    if (isset($analysis['key_insights'])) {
                        $sentimentData['key_insights'] = $analysis['key_insights'];
                    }
                    if (isset($analysis['recommendation'])) {
                        $sentimentData['recommendation'] = $analysis['recommendation'];
                    }
                    
                    // Adiciona métricas de marca (se disponíveis)
                    if (isset($analysis['total_mentions'])) {
                        $sentimentData['total_mentions'] = $analysis['total_mentions'];
                    }
                    if (isset($analysis['mentions_peak'])) {
                        $sentimentData['mentions_peak'] = $analysis['mentions_peak'];
                    }
                    if (isset($analysis['mentions_timeline'])) {
                        $sentimentData['mentions_timeline'] = $analysis['mentions_timeline'];
                    }
                    if (isset($analysis['sentiment_breakdown'])) {
                        $sentimentData['sentiment_breakdown'] = $analysis['sentiment_breakdown'];
                    }
                    if (isset($analysis['engagement_metrics'])) {
                        $sentimentData['engagement_metrics'] = $analysis['engagement_metrics'];
                    }
                    if (isset($analysis['engagement_score'])) {
                        $sentimentData['engagement_score'] = $analysis['engagement_score'];
                    }
                    if (isset($analysis['investor_confidence'])) {
                        $sentimentData['investor_confidence'] = $analysis['investor_confidence'];
                    }
                    if (isset($analysis['confidence_score'])) {
                        $sentimentData['confidence_score'] = $analysis['confidence_score'];
                    }
                    if (isset($analysis['brand_perception'])) {
                        $sentimentData['brand_perception'] = $analysis['brand_perception'];
                    }
                    if (isset($analysis['main_themes'])) {
                        $sentimentData['main_themes'] = $analysis['main_themes'];
                    }
                    if (isset($analysis['emotions_analysis'])) {
                        $sentimentData['emotions_analysis'] = $analysis['emotions_analysis'];
                    }
                    if (isset($analysis['actionable_insights'])) {
                        $sentimentData['actionable_insights'] = $analysis['actionable_insights'];
                    }
                    if (isset($analysis['improvement_opportunities'])) {
                        $sentimentData['improvement_opportunities'] = $analysis['improvement_opportunities'];
                    }
                    if (isset($analysis['risk_alerts'])) {
                        $sentimentData['risk_alerts'] = $analysis['risk_alerts'];
                    }
                    if (isset($analysis['strategic_analysis'])) {
                        $sentimentData['strategic_analysis'] = $analysis['strategic_analysis'];
                    }
                    
                    // Normaliza estrutura de raw_data (otimizado: reutiliza lógica)
                    $sentimentData['raw_data'] = $this->normalizeRawData($sentimentData['raw_data'] ?? [], $analysis);
                    
                    // Salva análise no banco de dados (com dados enriquecidos)
                    SentimentAnalysis::create($sentimentData);

                    $analyzedCount++;
                    $this->line("  {$stockSymbol->symbol}: {$analysis['sentiment']} (score: {$analysis['sentiment_score']})");

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

            $this->info(" Análise concluída! {$analyzedCount} ação(ões) analisada(s) com sucesso.");
            if ($errorCount > 0) {
                $this->warn(" {$errorCount} erro(s) durante a análise.");
            }

            Log::info('Agent Pedro: Análise de sentimento concluída', [
                'analyzed' => $analyzedCount,
                'errors' => $errorCount,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error(' Erro ao analisar sentimento: ' . $e->getMessage());
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

    /**
     * Normaliza estrutura de raw_data para formato consistente
     * Reutiliza lógica do OrchestrationController
     */
    protected function normalizeRawData($rawData, array $analysis): array
    {
        $normalizedRawData = [
            'articles' => [],
            '_analysis' => []
        ];
        
        // Se raw_data é array de artigos (estrutura antiga), converte
        if (is_array($rawData) && isset($rawData[0]) && is_array($rawData[0])) {
            $normalizedRawData['articles'] = $rawData;
        } elseif (isset($rawData['articles']) && is_array($rawData['articles'])) {
            $normalizedRawData['articles'] = $rawData['articles'];
            if (isset($rawData['_analysis']) && is_array($rawData['_analysis'])) {
                $normalizedRawData['_analysis'] = $rawData['_analysis'];
            }
        }
        
        // Adiciona novos dados em _analysis
        if (isset($analysis['digital_data'])) {
            $normalizedRawData['_analysis']['digital_data'] = $analysis['digital_data'];
        }
        if (isset($analysis['behavioral_data'])) {
            $normalizedRawData['_analysis']['behavioral_data'] = $analysis['behavioral_data'];
        }
        if (isset($analysis['strategic_insights'])) {
            $normalizedRawData['_analysis']['strategic_insights'] = $analysis['strategic_insights'];
        }
        if (isset($analysis['cost_optimization'])) {
            $normalizedRawData['_analysis']['cost_optimization'] = $analysis['cost_optimization'];
        }
        
        return $normalizedRawData;
    }
}

