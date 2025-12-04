<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use App\Services\LLMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Agente Key - Geração de Matéria Jornalística
 * 
 * FLUXO DOS AGENTES:
 * Este job representa o Agente Key, uma redatora veterana de jornal financeiro com
 * mais de 15 anos de experiência, responsável por transformar dados técnicos em
 * matérias jornalísticas profissionais, claras, objetivas e aprofundadas.
 * 
 * Fluxo:
 * 1. Recebe dados financeiros do Agente Júlia (via Analysis->financialData)
 * 2. Recebe análise completa de sentimento do Agente Pedro (via Analysis->sentimentAnalysis)
 * 3. Prepara todos os dados para o LLMService
 * 4. Gera matéria jornalística via LLMService (usa GeminiResponseService)
 * 5. Salva Article no banco com status 'pendente_revisao'
 * 6. Atualiza Analysis com article_id
 * 
 * Dados recebidos (do Agente Júlia):
 * - Dados financeiros completos (price, volume, market_cap, pe_ratio, etc.)
 * - Nome da empresa (company_name)
 * - Dados brutos (raw_data)
 * 
 * Dados recebidos (do Agente Pedro):
 * - Sentimento básico (sentiment, sentiment_score, contagens)
 * - Análise de mercado (market_analysis, macroeconomic_analysis)
 * - Métricas de marca (brand_perception, engagement_metrics, etc.)
 * - Insights estratégicos (actionable_insights, risk_alerts, etc.)
 * - Dados digitais e comportamentais (em raw_data._analysis)
 * 
 * Dados gerados:
 * - Título da matéria (title)
 * - Conteúdo em HTML (content)
 * - Recomendação extraída (recomendacao)
 * 
 * Próximo passo: NotifyReviewerJob (Agente PublishNotify)
 */
class DraftArticleJob implements ShouldQueue
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
            $financialData = $this->analysis->financialData;
            $sentimentAnalysis = $this->analysis->sentimentAnalysis;
            $stockSymbol = $this->analysis->stockSymbol;

            if (!$financialData || !$sentimentAnalysis || !$stockSymbol) {
                throw new \Exception("Dados insuficientes para gerar artigo");
            }

            // Prepara dados financeiros completos do Agente Júlia (alinhado com executeKey)
            $stockSymbol = $this->analysis->stockSymbol;
            $companyName = $stockSymbol->company_name ?? $this->analysis->company_name;
            
            $financialDataArray = [
                'price' => $financialData->price,
                'previous_close' => $financialData->previous_close,
                'change' => $financialData->change,
                'change_percent' => $financialData->change_percent,
                'volume' => $financialData->volume,
                'market_cap' => $financialData->market_cap,
                'pe_ratio' => $financialData->pe_ratio,
                'dividend_yield' => $financialData->dividend_yield,
                'high_52w' => $financialData->high_52w,
                'low_52w' => $financialData->low_52w,
                'company_name' => $companyName, // Importante para o prompt do Agente Key
            ];

            // Prepara dados de sentimento completos do Agente Pedro (alinhado com executeKey)
            // Inclui TODOS os campos enriquecidos para análise aprofundada
            $sentimentDataArray = [
                // Dados básicos de sentimento
                'sentiment' => $sentimentAnalysis->sentiment ?? 'neutral',
                'sentiment_score' => $sentimentAnalysis->sentiment_score ?? 0,
                'news_count' => $sentimentAnalysis->news_count ?? 0,
                'positive_count' => $sentimentAnalysis->positive_count ?? 0,
                'negative_count' => $sentimentAnalysis->negative_count ?? 0,
                'neutral_count' => $sentimentAnalysis->neutral_count ?? 0,
                'trending_topics' => $sentimentAnalysis->trending_topics,
                'news_sources' => $sentimentAnalysis->news_sources,
                
                // Análise de mercado e macroeconomia (do Agente Pedro)
                'market_analysis' => $sentimentAnalysis->market_analysis,
                'macroeconomic_analysis' => $sentimentAnalysis->macroeconomic_analysis,
                'key_insights' => $sentimentAnalysis->key_insights,
                'recommendation' => $sentimentAnalysis->recommendation,
                
                // Métricas de marca e percepção (do Agente Pedro)
                'total_mentions' => $sentimentAnalysis->total_mentions,
                'mentions_peak' => $sentimentAnalysis->mentions_peak,
                'mentions_timeline' => $sentimentAnalysis->mentions_timeline,
                'sentiment_breakdown' => $sentimentAnalysis->sentiment_breakdown,
                'engagement_metrics' => $sentimentAnalysis->engagement_metrics,
                'engagement_score' => $sentimentAnalysis->engagement_score,
                'investor_confidence' => $sentimentAnalysis->investor_confidence,
                'confidence_score' => $sentimentAnalysis->confidence_score,
                'brand_perception' => $sentimentAnalysis->brand_perception,
                'main_themes' => $sentimentAnalysis->main_themes,
                'emotions_analysis' => $sentimentAnalysis->emotions_analysis,
                
                // Insights estratégicos (do Agente Pedro)
                'actionable_insights' => $sentimentAnalysis->actionable_insights,
                'improvement_opportunities' => $sentimentAnalysis->improvement_opportunities,
                'risk_alerts' => $sentimentAnalysis->risk_alerts,
                'strategic_analysis' => $sentimentAnalysis->strategic_analysis,
                
                // Dados brutos (estrutura normalizada: articles + _analysis)
                'raw_data' => $sentimentAnalysis->raw_data,
            ];

            // Gera artigo usando LLM
            $service = new LLMService();
            $article = $service->generateArticle(
                $financialDataArray,
                $sentimentDataArray,
                $stockSymbol->symbol
            );

            // Extrai recomendação do conteúdo
            $recommendation = $this->extractRecommendation($article['content']);

            // Salva artigo
            $articleModel = Article::create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
                'financial_data_id' => $financialData->id,
                'sentiment_analysis_id' => $sentimentAnalysis->id,
                'title' => $article['title'],
                'content' => $article['content'],
                'status' => 'pendente_revisao',
                'recomendacao' => $recommendation,
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'analysis_id' => $this->analysis->id,
                    'financial_data_collected_at' => $financialData->collected_at?->toIso8601String(),
                    'sentiment_analyzed_at' => $sentimentAnalysis->analyzed_at?->toIso8601String(),
                ],
            ]);

            // Atualiza análise
            $this->analysis->update([
                'article_id' => $articleModel->id,
                'status' => 'pending_review',
                'completed_at' => now(),
            ]);

            Log::info('DraftArticleJob: Artigo gerado', [
                'analysis_id' => $this->analysis->id,
                'article_id' => $articleModel->id,
            ]);

        } catch (\Exception $e) {
            Log::error('DraftArticleJob: Erro ao gerar artigo', [
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

    /**
     * Extrai recomendação do conteúdo gerado
     */
    protected function extractRecommendation(string $content): ?string
    {
        if (preg_match('/Recomendação[:\s]+(.+?)(?:\n|$)/i', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/Recomenda-se\s+(.+?)(?:\.|$)/i', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

