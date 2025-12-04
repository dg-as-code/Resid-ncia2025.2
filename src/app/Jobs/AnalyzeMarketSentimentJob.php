<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\StockSymbol;
use App\Models\SentimentAnalysis;
use App\Services\NewsAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Agente Pedro - Análise de Sentimento do Mercado
 * 
 * FLUXO DOS AGENTES:
 * Este job representa o Agente Pedro, responsável por analisar sentimento de mercado,
 * opiniões da mídia e percepção de marca sobre uma empresa/ação.
 * 
 * Fluxo:
 * 1. Recebe dados financeiros do Agente Júlia (via Analysis)
 * 2. Busca notícias via NewsAnalysisService
 * 3. Analisa sentimento com LLM (Gemini)
 * 4. Gera análise completa incluindo:
 *    - Dados básicos de sentimento
 *    - Análise de mercado e macroeconomia
 *    - Métricas de marca e percepção
 *    - Insights estratégicos
 *    - Dados digitais e comportamentais
 * 5. Salva SentimentAnalysis no banco
 * 6. Atualiza Analysis com sentiment_analysis_id
 * 
 * Dados recebidos (do Agente Júlia):
 * - Dados financeiros completos (price, volume, market_cap, etc.)
 * - Nome da empresa (company_name)
 * - Dados brutos (raw_data)
 * 
 * Dados gerados (para o Agente Key):
 * - Sentimento básico (sentiment, sentiment_score, contagens)
 * - Análise de mercado (market_analysis, macroeconomic_analysis)
 * - Métricas de marca (brand_perception, engagement_metrics, etc.)
 * - Insights estratégicos (actionable_insights, risk_alerts, etc.)
 * - Dados digitais e comportamentais (em raw_data._analysis)
 * 
 * Próximo passo: DraftArticleJob (Agente Key)
 */
class AnalyzeMarketSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $analysis;

    /**
     * Create a new job instance.
     */
    public function __construct(Analysis $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $stockSymbol = $this->analysis->stockSymbol;
            
            if (!$stockSymbol) {
                throw new \Exception("StockSymbol não encontrado para análise {$this->analysis->id}");
            }

            // Chama serviço para analisar sentimento
            $service = new NewsAnalysisService();
            $articles = $service->searchNews(
                $stockSymbol->symbol,
                $stockSymbol->company_name ?? $this->analysis->company_name,
                20
            );

            // Busca dados financeiros recentes para contexto (alinhado com executePedro)
            // Usa dados do Agente Júlia (via Analysis->financialData)
            $financialDataModel = $this->analysis->financialData;
            $financialData = [];
            
            if ($financialDataModel) {
                // Dados completos do Agente Júlia (incluindo company_name e raw_data)
                $financialData = [
                    'price' => $financialDataModel->price,
                    'previous_close' => $financialDataModel->previous_close,
                    'change' => $financialDataModel->change,
                    'change_percent' => $financialDataModel->change_percent,
                    'volume' => $financialDataModel->volume,
                    'market_cap' => $financialDataModel->market_cap,
                    'pe_ratio' => $financialDataModel->pe_ratio,
                    'dividend_yield' => $financialDataModel->dividend_yield,
                    'high_52w' => $financialDataModel->high_52w,
                    'low_52w' => $financialDataModel->low_52w,
                    'company_name' => $stockSymbol->company_name ?? $this->analysis->company_name,
                    'raw_data' => $financialDataModel->raw_data,
                ];
            } else {
                // Fallback: usa dados do StockSymbol se FinancialData não estiver disponível
                $latestFinancial = $stockSymbol->latestFinancialData;
                if ($latestFinancial) {
                    $financialData = [
                        'price' => $latestFinancial->price,
                        'previous_close' => $latestFinancial->previous_close,
                        'change' => $latestFinancial->change,
                        'change_percent' => $latestFinancial->change_percent,
                        'volume' => $latestFinancial->volume,
                        'market_cap' => $latestFinancial->market_cap,
                        'pe_ratio' => $latestFinancial->pe_ratio,
                        'dividend_yield' => $latestFinancial->dividend_yield,
                        'high_52w' => $latestFinancial->high_52w,
                        'low_52w' => $latestFinancial->low_52w,
                        'company_name' => $stockSymbol->company_name ?? $this->analysis->company_name,
                        'raw_data' => $latestFinancial->raw_data,
                    ];
                }
            }
            
            $analysis = $service->analyzeSentiment(
                $articles,
                $stockSymbol->symbol,
                $stockSymbol->company_name ?? $this->analysis->company_name,
                $financialData
            );

            // Prepara dados para salvar (alinhado com executePedro do OrchestrationController)
            // Campos obrigatórios primeiro
            $sentimentData = [
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
                'sentiment' => $analysis['sentiment'] ?? 'neutral',
                'sentiment_score' => $analysis['sentiment_score'] ?? 0,
                'news_count' => $analysis['news_count'] ?? 0,
                'positive_count' => $analysis['positive_count'] ?? 0,
                'negative_count' => $analysis['negative_count'] ?? 0,
                'neutral_count' => $analysis['neutral_count'] ?? 0,
                'trending_topics' => is_array($analysis['trending_topics'] ?? null) 
                    ? implode(', ', $analysis['trending_topics']) 
                    : ($analysis['trending_topics'] ?? null),
                'news_sources' => $analysis['news_sources'] ?? null,
                'raw_data' => $analysis['raw_data'] ?? null, // Estrutura normalizada: {articles: [], _analysis: {}}
                'source' => 'news_api_llm',
                'analyzed_at' => now(),
            ];
            
            // Adiciona campos de análise de mercado e macroeconomia (se disponíveis)
            // Estes campos são gerados pelo LLM e enriquecem a análise
            try {
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
                
                // Adiciona métricas de marca e percepção (se disponíveis)
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
                
                // Adiciona insights estratégicos (se disponíveis)
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
            } catch (\Exception $e) {
                // Se houver erro ao adicionar campos novos (ex: migration não executada), ignora
                Log::warning('AnalyzeMarketSentimentJob: Campos novos não disponíveis na tabela', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Salva análise de sentimento
            $sentimentAnalysis = SentimentAnalysis::create($sentimentData);

            // Atualiza análise com sentiment_analysis_id
            $this->analysis->update([
                'sentiment_analysis_id' => $sentimentAnalysis->id,
                'status' => 'drafting_article',
            ]);

            Log::info('AnalyzeMarketSentimentJob: Sentimento analisado', [
                'analysis_id' => $this->analysis->id,
                'sentiment' => $analysis['sentiment'],
            ]);

        } catch (\Exception $e) {
            Log::error('AnalyzeMarketSentimentJob: Erro ao analisar sentimento', [
                'analysis_id' => $this->analysis->id,
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

