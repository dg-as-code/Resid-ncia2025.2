<?php

namespace App\Console\Commands;

use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use App\Services\LLMService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Agente Key: Jornalista experiente que redige conteúdo final
 * 
 * Responsabilidade: Gerar rascunho de matéria financeira baseado nos dados coletados
 * pela Júlia (dados financeiros) e Pedro (análise de sentimento).
 * 
 * Este comando consolida as informações dos outros agentes e gera um rascunho
 * de matéria usando IA (LLM) para redação.
 */
class AgentKeyCompose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:key:compose 
                            {--symbol= : Símbolo da ação para gerar matéria (opcional)}
                            {--force : Forçar geração mesmo sem novos dados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agente Key: Gera rascunho de matéria financeira baseado nos dados dos outros agentes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(' Agente Key iniciando composição de matéria...');

        try {
            $symbol = $this->option('symbol');
            $force = $this->option('force');
            $service = new LLMService();
            $generatedCount = 0;
            $errorCount = 0;

            // Determina quais símbolos processar
            $symbolsToProcess = $this->getSymbolsToProcess($symbol, $force);

            if (empty($symbolsToProcess)) {
                $this->warn(' Nenhuma ação com dados suficientes para gerar matéria.');
                return Command::SUCCESS;
            }

            $this->info(" Gerando matérias para " . count($symbolsToProcess) . " ação(ões)...");

            $bar = $this->output->createProgressBar(count($symbolsToProcess));
            $bar->start();

            foreach ($symbolsToProcess as $stockSymbol) {
                try {
                    // Busca dados financeiros mais recentes (Júlia)
                    $financialData = FinancialData::where('stock_symbol_id', $stockSymbol->id)
                        ->latest('collected_at')
                        ->first();

                    // Busca análise de sentimento mais recente (Pedro)
                    $sentimentAnalysis = SentimentAnalysis::where('stock_symbol_id', $stockSymbol->id)
                        ->latest('analyzed_at')
                        ->first();

                    // Verifica se há dados suficientes
                    if (!$financialData || !$sentimentAnalysis) {
                        if (!$force) {
                            $this->line("  {$stockSymbol->symbol}: Dados insuficientes (financeiro: " . ($financialData ? '✓' : '✗') . ", sentimento: " . ($sentimentAnalysis ? '✓' : '✗') . ")");
                            $bar->advance();
                            continue;
                        }
                    }

                    // Prepara dados para o LLM
                    $financialDataArray = $financialData ? [
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
                        'company_name' => $stockSymbol->company_name ?? null,
                    ] : [];

                    $sentimentDataArray = $sentimentAnalysis ? [
                        'sentiment' => $sentimentAnalysis->sentiment,
                        'sentiment_score' => $sentimentAnalysis->sentiment_score,
                        'news_count' => $sentimentAnalysis->news_count,
                        'positive_count' => $sentimentAnalysis->positive_count,
                        'negative_count' => $sentimentAnalysis->negative_count,
                        'neutral_count' => $sentimentAnalysis->neutral_count,
                        'trending_topics' => $sentimentAnalysis->trending_topics,
                        // Novos campos de análise de mercado
                        'market_analysis' => $sentimentAnalysis->market_analysis,
                        'macroeconomic_analysis' => $sentimentAnalysis->macroeconomic_analysis,
                        'key_insights' => $sentimentAnalysis->key_insights,
                        'recommendation' => $sentimentAnalysis->recommendation,
                        // Métricas de marca
                        'total_mentions' => $sentimentAnalysis->total_mentions,
                        'mentions_peak' => $sentimentAnalysis->mentions_peak,
                        'sentiment_breakdown' => $sentimentAnalysis->sentiment_breakdown,
                        'engagement_metrics' => $sentimentAnalysis->engagement_metrics,
                        'engagement_score' => $sentimentAnalysis->engagement_score,
                        'investor_confidence' => $sentimentAnalysis->investor_confidence,
                        'confidence_score' => $sentimentAnalysis->confidence_score,
                        'brand_perception' => $sentimentAnalysis->brand_perception,
                        'main_themes' => $sentimentAnalysis->main_themes,
                        'actionable_insights' => $sentimentAnalysis->actionable_insights,
                        'improvement_opportunities' => $sentimentAnalysis->improvement_opportunities,
                        'risk_alerts' => $sentimentAnalysis->risk_alerts,
                        'strategic_analysis' => $sentimentAnalysis->strategic_analysis,
                        // Dados digitais e comportamentais (de raw_data)
                        'raw_data' => $sentimentAnalysis->raw_data,
                    ] : [];

                    // Gera matéria usando LLM
                    $article = $service->generateArticle(
                        $financialDataArray,
                        $sentimentDataArray,
                        $stockSymbol->symbol
                    );

                    // Extrai recomendação do conteúdo
                    $recommendation = $this->extractRecommendation($article['content']);

                    // Salva rascunho no banco de dados
                    Article::create([
                        'stock_symbol_id' => $stockSymbol->id,
                        'symbol' => $stockSymbol->symbol,
                        'financial_data_id' => $financialData?->id,
                        'sentiment_analysis_id' => $sentimentAnalysis?->id,
                        'title' => $article['title'],
                        'content' => $article['content'],
                        'status' => 'pendente_revisao',
                        'recomendacao' => $recommendation,
                        'metadata' => [
                            'generated_at' => now()->toIso8601String(),
                            'financial_data_collected_at' => $financialData?->collected_at?->toIso8601String(),
                            'sentiment_analyzed_at' => $sentimentAnalysis?->analyzed_at?->toIso8601String(),
                        ],
                    ]);

                    $generatedCount++;
                    $this->line("  {$stockSymbol->symbol}: Matéria gerada");

                    Log::info('Agent Key: Matéria gerada', [
                        'symbol' => $stockSymbol->symbol,
                        'title' => $article['title'],
                    ]);
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Agent Key: Erro ao gerar matéria', [
                        'symbol' => $stockSymbol->symbol,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $this->info(" Composição concluída! {$generatedCount} matéria(s) gerada(s) com sucesso.");
            $this->info(' Status: Pendente de revisão humana');
            if ($errorCount > 0) {
                $this->warn(" {$errorCount} erro(s) durante a geração.");
            }

            Log::info('Agent Key: Composição de matérias concluída', [
                'generated' => $generatedCount,
                'errors' => $errorCount,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error(' Erro ao gerar matéria: ' . $e->getMessage());
            Log::error('Agent Key: Erro ao gerar matéria', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Obtém símbolos para processar baseado nas opções
     * 
     * @param string|null $symbol
     * @param bool $force
     * @return array
     */
    protected function getSymbolsToProcess(?string $symbol, bool $force): array
    {
        if ($symbol) {
            $stockSymbol = StockSymbol::where('symbol', $symbol)->where('is_active', true)->first();
            return $stockSymbol ? [$stockSymbol] : [];
        }

        // Busca ações que têm dados financeiros e análise de sentimento recentes
        $cutoffDate = Carbon::now()->subHours(24); // Últimas 24 horas

        $query = StockSymbol::active()
            ->whereHas('financialData', function ($q) use ($cutoffDate) {
                $q->where('collected_at', '>=', $cutoffDate);
            })
            ->whereHas('sentimentAnalyses', function ($q) use ($cutoffDate) {
                $q->where('analyzed_at', '>=', $cutoffDate);
            });

        // Se não forçar, filtra apenas ações que não têm matéria pendente recente
        if (!$force) {
            $query->whereDoesntHave('articles', function ($q) {
                $q->where('status', 'pendente_revisao')
                  ->where('created_at', '>=', Carbon::now()->subHours(2));
            });
        }

        return $query->get()->all();
    }

    /**
     * Extrai recomendação do conteúdo gerado
     * 
     * @param string $content
     * @return string|null
     */
    protected function extractRecommendation(string $content): ?string
    {
        // Procura por padrões de recomendação no conteúdo
        if (preg_match('/Recomendação[:\s]+(.+?)(?:\n|$)/i', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/Recomenda-se\s+(.+?)(?:\.|$)/i', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

