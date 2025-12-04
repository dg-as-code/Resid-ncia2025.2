<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use App\Services\YahooFinanceService;
use App\Services\NewsAnalysisService;
use App\Services\LLMService;

/**
 * Controller de Orquestração Completa
 * 
 * Executa o fluxo completo: Júlia → Pedro → Key → PublishNotify
 * Segue o fluxo especificado no prompt de orquestração
 */
class OrchestrationController extends Controller
{
    protected $logs = [];

    /**
     * Orquestra o fluxo completo de análise
     * 
     * Recebe apenas o nome da empresa e executa todo o pipeline
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function orchestrate(Request $request): JsonResponse
    {
        try {
            $companyName = trim($request->input('company_name', ''));
            
            if (empty($companyName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nome da empresa é obrigatório',
                ], 422);
            }

            $this->logs = [];
            $this->addLog('Sistema', "Iniciando análise para: {$companyName}");

            // PASSO 1: Agente Júlia - Coleta dados financeiros
            $financialData = $this->executeJulia($companyName);
            
            if (!$financialData || !is_array($financialData) || empty($financialData)) {
                $this->addLog('Sistema', "❌ Falha na coleta de dados financeiros. Verifique os logs acima para detalhes.");
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao coletar dados financeiros. Verifique os logs para mais detalhes.',
                    'logs' => $this->logs,
                    'hint' => 'Verifique se: 1) As migrations foram executadas (php artisan migrate), 2) A API Gemini está configurada, 3) O banco de dados está acessível',
                ], 500);
            }
            
            // Valida campos essenciais
            if (!isset($financialData['symbol']) || empty($financialData['symbol'])) {
                $this->addLog('Sistema', "❌ Dados financeiros sem symbol válido.");
                return response()->json([
                    'success' => false,
                    'message' => 'Dados financeiros inválidos: symbol não encontrado.',
                    'logs' => $this->logs,
                ], 500);
            }

            // PASSO 2: Agente Pedro - Análise de sentimento
            $sentimentData = $this->executePedro($companyName, $financialData);
            
            if (!$sentimentData || !is_array($sentimentData) || empty($sentimentData)) {
                $this->addLog('Sistema', "❌ Falha na análise de sentimento. Verifique os logs acima para detalhes.");
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao analisar sentimento. Verifique os logs para mais detalhes.',
                    'logs' => $this->logs,
                    'hint' => 'Verifique se: 1) As migrations foram executadas, 2) A API Gemini está configurada, 3) A News API está configurada',
                ], 500);
            }
            
            // Valida campos obrigatórios do sentimento
            if (empty($sentimentData['sentiment']) || !isset($sentimentData['sentiment_score'])) {
                $this->addLog('Sistema', "❌ Dados de sentimento incompletos.");
                return response()->json([
                    'success' => false,
                    'message' => 'Dados de sentimento incompletos. Sentimento ou score não encontrados.',
                    'logs' => $this->logs,
                ], 500);
            }

            // PASSO 3: Agente Key - Geração de matéria em HTML
            $article = $this->executeKey($companyName, $financialData, $sentimentData);
            
            if (!$article || !($article instanceof \App\Models\Article)) {
                $this->addLog('Sistema', "❌ Falha na geração de matéria. Verifique os logs acima para detalhes.");
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar matéria. Verifique os logs para mais detalhes.',
                    'logs' => $this->logs,
                    'hint' => 'Verifique se: 1) A API Gemini está configurada, 2) Os dados de entrada são válidos, 3) O banco de dados está acessível',
                ], 500);
            }
            
            // Valida se artigo tem campos obrigatórios
            if (empty($article->title) || empty($article->content)) {
                $this->addLog('Sistema', "❌ Artigo gerado sem título ou conteúdo.");
                return response()->json([
                    'success' => false,
                    'message' => 'Artigo gerado sem título ou conteúdo.',
                    'logs' => $this->logs,
                ], 500);
            }

            // PASSO 4: Agente PublishNotify - Envia notificação
            $this->executePublishNotify($article);

            // PASSO 5: Retorna resultado e PARA para revisão humana
            return response()->json([
                'success' => true,
                'status' => 'pending_review',
                'message' => '📧 E-mail enviado para o editor. Aguardando aprovação...',
                'article_id' => $article->id,
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'html_content' => $article->content, // Já em HTML
                    'symbol' => $article->symbol,
                    'status' => $article->status,
                ],
                'financial_data' => $financialData,
                'sentiment_data' => $sentimentData,
                'logs' => $this->logs,
            ], 200);

        } catch (\Exception $e) {
            Log::error('OrchestrationController: Erro na orquestração', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro na orquestração: ' . $e->getMessage(),
                'logs' => $this->logs,
            ], 500);
        }
    }

    /**
     * Executa Agente Júlia e retorna dados financeiros em JSON
     */
    protected function executeJulia(string $companyName): ?array
    {
        $this->addLog('Julia', "Iniciado... Coletando dados de {$companyName}...");

        try {
            $service = new YahooFinanceService();

            // getQuote() já retorna dados normalizados em formato array
            $quote = $service->getQuote($companyName);

            if (!$quote || !is_array($quote) || empty($quote)) {
                $this->addLog('Julia', "Erro: Não foi possível coletar dados. getQuote() retornou null ou vazio.");
                Log::error('OrchestrationController: getQuote retornou null ou vazio', [
                    'company_name' => $companyName,
                    'quote' => $quote,
                    'quote_type' => gettype($quote),
                ]);
                return null;
            }

            // Usa symbol do quote se disponível, senão usa companyName
            // getQuote() sempre retorna dados (mesmo mockados), então podemos continuar
            $symbol = $quote['symbol'] ?? $companyName;
            
            // Se não tem symbol nem company_name no quote, usa companyName como fallback
            if (empty($quote['symbol']) && empty($quote['company_name'])) {
                $this->addLog('Julia', "Aviso: Dados coletados não contêm symbol nem company_name. Usando '{$companyName}' como fallback.");
                Log::warning('OrchestrationController: Dados coletados sem symbol/company_name, usando fallback', [
                    'company_name' => $companyName,
                    'quote' => $quote,
                ]);
            }
            
            // Busca ou cria StockSymbol (otimizado: usa método helper)
            $stockSymbol = $this->getOrCreateStockSymbol($symbol, $quote['company_name'] ?? $companyName, 'Julia');
            
            if (!$stockSymbol) {
                return null;
            }

            // Cria FinancialData diretamente dos dados normalizados
            try {
                $financial = FinancialData::create([
                    'stock_symbol_id' => $stockSymbol->id,
                    'symbol' => $symbol,
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
                    'raw_data' => $quote['raw_data'] ?? null,
                    'collected_at' => $quote['collected_at'] ?? now(),
                ]);

                if (!$financial) {
                    $this->addLog('Julia', "Erro: Falha ao salvar no banco (create retornou null)");
                    Log::error('OrchestrationController: FinancialData::create retornou null', [
                        'stock_symbol_id' => $stockSymbol->id,
                        'symbol' => $symbol,
                    ]);
                    return null;
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // Se a tabela não existe, tenta criar via migration
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    $this->addLog('Julia', "Tabela financial_data não existe. Executando migrations...");
                    Artisan::call('migrate', ['--force' => true]);
                    $this->addLog('Julia', "Migrations executadas. Tentando novamente...");
                    
                    // Tenta novamente após migrations
                    try {
                        $financial = FinancialData::create([
                            'stock_symbol_id' => $stockSymbol->id,
                            'symbol' => $symbol,
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
                            'raw_data' => $quote['raw_data'] ?? null,
                            'collected_at' => $quote['collected_at'] ?? now(),
                        ]);
                    } catch (\Exception $e2) {
                        $this->addLog('Julia', "Erro após migrations: " . $e2->getMessage());
                        Log::error('OrchestrationController: Erro ao criar FinancialData após migrations', [
                            'error' => $e2->getMessage(),
                            'trace' => $e2->getTraceAsString(),
                        ]);
                        return null;
                    }
                } else {
                    $this->addLog('Julia', "Erro ao salvar no banco: " . $e->getMessage());
                    Log::error('OrchestrationController: Erro ao criar FinancialData', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->addLog('Julia', "Erro inesperado ao salvar: " . $e->getMessage());
                Log::error('OrchestrationController: Erro inesperado ao criar FinancialData', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return null;
            }

            // Retorna dados completos em formato JSON (para uso pelo Agente Pedro e Key)
            $financialData = [
                'id' => $financial->id,
                'symbol' => $financial->symbol ?? $symbol,
                'company_name' => $stockSymbol->company_name ?? $companyName,
                'price' => $financial->price,
                'previous_close' => $financial->previous_close,
                'change' => $financial->change,
                'change_percent' => $financial->change_percent,
                'volume' => $financial->volume,
                'market_cap' => $financial->market_cap,
                'pe_ratio' => $financial->pe_ratio,
                'dividend_yield' => $financial->dividend_yield,
                'high_52w' => $financial->high_52w,
                'low_52w' => $financial->low_52w,
                'raw_data' => $financial->raw_data, // Inclui raw_data para uso pelo Agente Pedro
                'collected_at' => $financial->collected_at?->toIso8601String(),
            ];

            $this->addLog('Julia', "✅ Concluído com sucesso!");
            $this->addLog('Julia', "Symbol: {$financialData['symbol']}, Preço: R$ " . number_format($financialData['price'] ?? 0, 2, ',', '.'));

            Log::info('OrchestrationController: Agente Júlia concluído com sucesso', [
                'company_name' => $companyName,
                'symbol' => $financialData['symbol'],
                'financial_id' => $financialData['id'],
            ]);

            return $financialData;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorClass = get_class($e);
            
            $this->addLog('Julia', "❌ Erro: " . $errorMessage);
            $this->addLog('Julia', "Tipo: {$errorClass}");
            
            Log::error('OrchestrationController: Erro no Agente Júlia', [
                'error' => $errorMessage,
                'error_class' => $errorClass,
                'trace' => $e->getTraceAsString(),
                'company_name' => $companyName,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Trata erros de banco de dados (otimizado: usa método helper)
            $this->handleDatabaseError($e, 'Julia', 'executeJulia');
            
            return null;
        }
    }

    /**
     * Executa Agente Pedro e retorna análise de sentimento em JSON
     */
    protected function executePedro(string $companyName, array $financialData): ?array
    {
        $symbol = $financialData['symbol'] ?? $companyName;
        
        $this->addLog('Pedro', "Iniciado... Analisando sentimento de {$symbol}...");

        try {
            $service = new NewsAnalysisService();

            // Busca ou cria StockSymbol (otimizado: usa método helper)
            $stockSymbol = $this->getOrCreateStockSymbol($symbol, $companyName, 'Pedro');
            
            if (!$stockSymbol) {
                return null;
            }

            // Busca notícias
            $articles = $service->searchNews(
                $symbol,
                $companyName,
                20
            );

            // Valida se há notícias antes de analisar
            if (empty($articles)) {
                $this->addLog('Pedro', "Aviso: Nenhuma notícia encontrada, usando sentimento padrão");
            }

            // Valida se financialData tem campos mínimos necessários
            if (empty($financialData) || !is_array($financialData)) {
                $this->addLog('Pedro', "Aviso: financialData vazio ou inválido, usando array vazio");
                $financialData = [];
            }
            
            // Analisa sentimento com LLM (análise enriquecida)
            $analysis = $service->analyzeSentiment($articles, $symbol, $companyName, $financialData);
            
            // Valida se análise retornou dados válidos
            if (empty($analysis) || !is_array($analysis)) {
                $this->addLog('Pedro', "Erro: analyzeSentiment retornou dados inválidos");
                Log::error('OrchestrationController: analyzeSentiment retornou dados inválidos', [
                    'analysis' => $analysis,
                ]);
                return null;
            }
            
            // Valida campos obrigatórios
            if (empty($analysis['sentiment']) || !isset($analysis['sentiment_score'])) {
                $this->addLog('Pedro', "Erro: Análise sem sentiment ou sentiment_score");
                Log::error('OrchestrationController: Análise sem campos obrigatórios', [
                    'has_sentiment' => !empty($analysis['sentiment']),
                    'has_sentiment_score' => isset($analysis['sentiment_score']),
                ]);
                return null;
            }

            // Prepara dados para salvar (campos obrigatórios primeiro)
            $sentimentData = [
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $symbol,
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
            
            // Adiciona campos de análise de mercado e macroeconomia
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
                
                // Normaliza estrutura de raw_data (otimizado: usa método helper)
                // Preserva raw_data original se existir, senão usa articles da análise
                $rawDataToNormalize = $sentimentData['raw_data'] ?? $analysis['raw_data'] ?? [];
                $sentimentData['raw_data'] = $this->normalizeRawData($rawDataToNormalize, $analysis);
            } catch (\Exception $e) {
                // Se houver erro, ignora campos novos (migration não executada)
                Log::warning('OrchestrationController: Campos novos não disponíveis na tabela', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Salva no banco (com dados enriquecidos da LLM)
            try {
                $sentimentAnalysis = SentimentAnalysis::create($sentimentData);
            } catch (\Illuminate\Database\QueryException $e) {
                // Se a tabela não existe, tenta criar via migration
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    $this->addLog('Pedro', "Tabela sentiment_analysis não existe. Executando migrations...");
                    Artisan::call('migrate', ['--force' => true]);
                    $this->addLog('Pedro', "Migrations executadas. Tentando criar SentimentAnalysis novamente...");
                    
                    // Tenta novamente após migrations
                    try {
                        $sentimentAnalysis = SentimentAnalysis::create($sentimentData);
                    } catch (\Exception $e2) {
                        $this->addLog('Pedro', "Erro ao criar SentimentAnalysis após migrations: " . $e2->getMessage());
                        Log::error('OrchestrationController: Erro ao criar SentimentAnalysis após migrations', [
                            'error' => $e2->getMessage(),
                            'trace' => $e2->getTraceAsString(),
                        ]);
                        return null;
                    }
                } else {
                    $this->addLog('Pedro', "Erro ao criar SentimentAnalysis: " . $e->getMessage());
                    Log::error('OrchestrationController: Erro ao criar SentimentAnalysis', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->addLog('Pedro', "Erro inesperado ao criar SentimentAnalysis: " . $e->getMessage());
                Log::error('OrchestrationController: Erro inesperado ao criar SentimentAnalysis', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return null;
            }
            
            if (!$sentimentAnalysis) {
                $this->addLog('Pedro', "Erro: Falha ao salvar análise (create retornou null)");
                Log::error('OrchestrationController: SentimentAnalysis::create retornou null');
                return null;
            }

            // Retorna dados completos em formato JSON (incluindo todos os campos enriquecidos para o Agente Key)
            $sentimentResponse = [
                'id' => $sentimentAnalysis->id,
                'symbol' => $sentimentAnalysis->symbol,
                'sentiment' => $sentimentAnalysis->sentiment,
                'sentiment_score' => $sentimentAnalysis->sentiment_score,
                'news_count' => $sentimentAnalysis->news_count,
                'positive_count' => $sentimentAnalysis->positive_count,
                'negative_count' => $sentimentAnalysis->negative_count,
                'neutral_count' => $sentimentAnalysis->neutral_count,
                'trending_topics' => $sentimentAnalysis->trending_topics,
                'news_sources' => $sentimentAnalysis->news_sources,
                'analyzed_at' => $sentimentAnalysis->analyzed_at?->toIso8601String(),
                // Análise de mercado e macroeconomia
                'market_analysis' => $sentimentAnalysis->market_analysis,
                'macroeconomic_analysis' => $sentimentAnalysis->macroeconomic_analysis,
                'key_insights' => $sentimentAnalysis->key_insights,
                'recommendation' => $sentimentAnalysis->recommendation,
                // Métricas de marca e percepção
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
                // Insights estratégicos
                'actionable_insights' => $sentimentAnalysis->actionable_insights,
                'improvement_opportunities' => $sentimentAnalysis->improvement_opportunities,
                'risk_alerts' => $sentimentAnalysis->risk_alerts,
                'strategic_analysis' => $sentimentAnalysis->strategic_analysis,
                // Dados brutos (com estrutura normalizada)
                'raw_data' => $sentimentAnalysis->raw_data,
            ];

            $this->addLog('Pedro', "✅ Concluído com sucesso!");
            $this->addLog('Pedro', "Sentiment: {$sentimentResponse['sentiment']}, Score: " . number_format($sentimentResponse['sentiment_score'], 2, ',', '.'));

            Log::info('OrchestrationController: Agente Pedro concluído com sucesso', [
                'company_name' => $companyName,
                'symbol' => $sentimentResponse['symbol'],
                'sentiment_id' => $sentimentResponse['id'],
            ]);

            return $sentimentResponse;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorClass = get_class($e);
            
            $this->addLog('Pedro', "❌ Erro: " . $errorMessage);
            $this->addLog('Pedro', "Tipo: {$errorClass}");
            
            Log::error('OrchestrationController: Erro no Agente Pedro', [
                'error' => $errorMessage,
                'error_class' => $errorClass,
                'trace' => $e->getTraceAsString(),
                'company_name' => $companyName,
                'symbol' => $symbol,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Trata erros de banco de dados (otimizado: usa método helper)
            $this->handleDatabaseError($e, 'Pedro', 'executePedro');
            
            return null;
        }
    }

    /**
     * Executa Agente Key - Redatora Veterana de Jornal Financeiro
     * 
     * Transforma os dados coletados pelos Agentes Júlia e Pedro em uma
     * matéria jornalística profissional, clara, objetiva e aprofundada.
     * 
     * @param string $companyName Nome da empresa
     * @param array $financialData Dados financeiros do Agente Júlia
     * @param array $sentimentData Análise completa de sentimento do Agente Pedro
     * @return Article|null Artigo gerado ou null em caso de erro
     */
    protected function executeKey(string $companyName, array $financialData, array $sentimentData): ?Article
    {
        $symbol = $financialData['symbol'] ?? $companyName;
        
        $this->addLog('Key', "Iniciado... Redatora veterana transformando dados em matéria jornalística para {$symbol}...");

        try {
            $service = new LLMService();

            // Busca ou cria StockSymbol (otimizado: usa método helper)
            $stockSymbol = $this->getOrCreateStockSymbol($symbol, $companyName, 'Key');
            
            if (!$stockSymbol) {
                $this->addLog('Key', "Erro: Não foi possível obter ou criar StockSymbol");
                Log::error('OrchestrationController: Não foi possível obter ou criar StockSymbol em executeKey', [
                    'symbol' => $symbol,
                    'company_name' => $companyName,
                ]);
                return null;
            }

            // Prepara dados para LLM (incluindo novos campos)
            $financialDataArray = [
                'price' => $financialData['price'] ?? null,
                'previous_close' => $financialData['previous_close'] ?? null,
                'change' => $financialData['change'] ?? null,
                'change_percent' => $financialData['change_percent'] ?? null,
                'volume' => $financialData['volume'] ?? null,
                'market_cap' => $financialData['market_cap'] ?? null,
                'pe_ratio' => $financialData['pe_ratio'] ?? null,
                'dividend_yield' => $financialData['dividend_yield'] ?? null,
                'high_52w' => $financialData['high_52w'] ?? null,
                'low_52w' => $financialData['low_52w'] ?? null,
                'company_name' => $financialData['company_name'] ?? $companyName,
            ];

            // Prepara dados de sentimento completos para o Agente Key (redatora jornalística)
            // Inclui todos os dados coletados pelo Agente Pedro para análise aprofundada
            $sentimentDataArray = [
                // Dados básicos de sentimento
                'sentiment' => $sentimentData['sentiment'] ?? 'neutral',
                'sentiment_score' => $sentimentData['sentiment_score'] ?? 0,
                'news_count' => $sentimentData['news_count'] ?? 0,
                'positive_count' => $sentimentData['positive_count'] ?? 0,
                'negative_count' => $sentimentData['negative_count'] ?? 0,
                'neutral_count' => $sentimentData['neutral_count'] ?? 0,
                'trending_topics' => $sentimentData['trending_topics'] ?? null,
                'news_sources' => $sentimentData['news_sources'] ?? null,
                
                // Análise de mercado e macroeconomia (do Agente Pedro)
                'market_analysis' => $sentimentData['market_analysis'] ?? null,
                'macroeconomic_analysis' => $sentimentData['macroeconomic_analysis'] ?? null,
                'key_insights' => $sentimentData['key_insights'] ?? null,
                'recommendation' => $sentimentData['recommendation'] ?? null,
                
                // Métricas de marca e percepção (do Agente Pedro)
                'total_mentions' => $sentimentData['total_mentions'] ?? null,
                'mentions_peak' => $sentimentData['mentions_peak'] ?? null,
                'mentions_timeline' => $sentimentData['mentions_timeline'] ?? null,
                'sentiment_breakdown' => $sentimentData['sentiment_breakdown'] ?? null,
                'engagement_metrics' => $sentimentData['engagement_metrics'] ?? null,
                'engagement_score' => $sentimentData['engagement_score'] ?? null,
                'investor_confidence' => $sentimentData['investor_confidence'] ?? null,
                'confidence_score' => $sentimentData['confidence_score'] ?? null,
                'brand_perception' => $sentimentData['brand_perception'] ?? null,
                'main_themes' => $sentimentData['main_themes'] ?? null,
                'emotions_analysis' => $sentimentData['emotions_analysis'] ?? null,
                
                // Insights estratégicos (do Agente Pedro)
                'actionable_insights' => $sentimentData['actionable_insights'] ?? null,
                'improvement_opportunities' => $sentimentData['improvement_opportunities'] ?? null,
                'risk_alerts' => $sentimentData['risk_alerts'] ?? null,
                'strategic_analysis' => $sentimentData['strategic_analysis'] ?? null,
                
                // Dados brutos (estrutura normalizada: articles + _analysis)
                'raw_data' => $sentimentData['raw_data'] ?? null,
            ];

            // Gera matéria (já retorna HTML do parseArticleResponse())
            $articleData = $service->generateArticle(
                $financialDataArray,
                $sentimentDataArray,
                $symbol
            );

            // Valida se articleData é válido
            if (!$articleData || !is_array($articleData)) {
                $this->addLog('Key', "Erro: generateArticle retornou dados inválidos");
                Log::error('OrchestrationController: generateArticle retornou dados inválidos', [
                    'article_data' => $articleData,
                ]);
                return null;
            }

            // Valida campos obrigatórios
            if (empty($articleData['title']) || empty($articleData['content'])) {
                $this->addLog('Key', "Erro: Artigo gerado sem título ou conteúdo");
                Log::error('OrchestrationController: Artigo gerado sem título ou conteúdo', [
                    'has_title' => !empty($articleData['title']),
                    'has_content' => !empty($articleData['content']),
                ]);
                return null;
            }

            // parseArticleResponse() já retorna HTML, então não precisa converter novamente
            // Apenas valida e limpa se necessário
            $htmlContent = $this->ensureValidHtml($articleData['content'] ?? '');

            // Valida se HTML não está vazio após processamento
            if (empty($htmlContent)) {
                $this->addLog('Key', "Erro: Conteúdo HTML vazio após processamento");
                Log::error('OrchestrationController: Conteúdo HTML vazio após processamento');
                return null;
            }

            // Extrai recomendação
            $recommendation = $this->extractRecommendation($htmlContent);

            // Busca IDs dos dados (otimizado: usa relacionamentos quando possível)
            $financialDataModel = null;
            $sentimentAnalysisModel = null;
            
            // Busca FinancialData (otimizado: tenta por ID primeiro, depois relacionamento)
            if (isset($financialData['id']) && !empty($financialData['id'])) {
                try {
                    $financialDataModel = FinancialData::find($financialData['id']);
                } catch (\Exception $e) {
                    Log::warning('OrchestrationController: Erro ao buscar FinancialData por ID', [
                        'id' => $financialData['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Se não encontrou por ID, usa relacionamento (mais eficiente)
            if (!$financialDataModel && $stockSymbol) {
                try {
                    $financialDataModel = $stockSymbol->latestFinancialData;
                } catch (\Exception $e) {
                    Log::warning('OrchestrationController: Erro ao buscar FinancialData via relacionamento', [
                        'symbol' => $symbol,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Busca SentimentAnalysis (otimizado: tenta por ID primeiro, depois relacionamento)
            if (isset($sentimentData['id']) && !empty($sentimentData['id'])) {
                try {
                    $sentimentAnalysisModel = SentimentAnalysis::find($sentimentData['id']);
                } catch (\Exception $e) {
                    Log::warning('OrchestrationController: Erro ao buscar SentimentAnalysis por ID', [
                        'id' => $sentimentData['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Se não encontrou por ID, usa relacionamento (mais eficiente)
            if (!$sentimentAnalysisModel && $stockSymbol) {
                try {
                    $sentimentAnalysisModel = $stockSymbol->latestSentimentAnalysis;
                } catch (\Exception $e) {
                    Log::warning('OrchestrationController: Erro ao buscar SentimentAnalysis via relacionamento', [
                        'symbol' => $symbol,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Logs informativos
            if (!$financialDataModel) {
                $this->addLog('Key', "Aviso: FinancialData não encontrado, artigo será criado sem relacionamento");
                Log::warning('OrchestrationController: FinancialData não encontrado', [
                    'financial_data_id' => $financialData['id'] ?? null,
                    'symbol' => $symbol,
                ]);
            }
            
            if (!$sentimentAnalysisModel) {
                $this->addLog('Key', "Aviso: SentimentAnalysis não encontrado, artigo será criado sem relacionamento");
                Log::warning('OrchestrationController: SentimentAnalysis não encontrado', [
                    'sentiment_analysis_id' => $sentimentData['id'] ?? null,
                    'symbol' => $symbol,
                ]);
            }

            // Salva artigo
            try {
                $article = Article::create([
                    'stock_symbol_id' => $stockSymbol->id,
                    'symbol' => $symbol,
                    'financial_data_id' => $financialDataModel?->id,
                    'sentiment_analysis_id' => $sentimentAnalysisModel?->id,
                    'title' => $articleData['title'],
                    'content' => $htmlContent, // Salva como HTML
                    'status' => 'pendente_revisao',
                    'recomendacao' => $recommendation,
                    'metadata' => [
                        'generated_at' => now()->toIso8601String(),
                        'financial_data_collected_at' => $financialData['collected_at'] ?? null,
                        'sentiment_analyzed_at' => $sentimentData['analyzed_at'] ?? null,
                    ],
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Se a tabela não existe, tenta criar via migration
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    $this->addLog('Key', "Tabela articles não existe. Executando migrations...");
                    Artisan::call('migrate', ['--force' => true]);
                    $this->addLog('Key', "Migrations executadas. Tentando criar artigo novamente...");
                    
                    // Tenta novamente após migrations
                    try {
                        $article = Article::create([
                            'stock_symbol_id' => $stockSymbol->id,
                            'symbol' => $symbol,
                            'financial_data_id' => $financialDataModel?->id,
                            'sentiment_analysis_id' => $sentimentAnalysisModel?->id,
                            'title' => $articleData['title'],
                            'content' => $htmlContent,
                            'status' => 'pendente_revisao',
                            'recomendacao' => $recommendation,
                            'metadata' => [
                                'generated_at' => now()->toIso8601String(),
                                'financial_data_collected_at' => $financialData['collected_at'] ?? null,
                                'sentiment_analyzed_at' => $sentimentData['analyzed_at'] ?? null,
                            ],
                        ]);
                    } catch (\Exception $e2) {
                        $this->addLog('Key', "Erro ao criar artigo após migrations: " . $e2->getMessage());
                        Log::error('OrchestrationController: Erro ao criar Article após migrations', [
                            'error' => $e2->getMessage(),
                            'trace' => $e2->getTraceAsString(),
                        ]);
                        return null;
                    }
                } else {
                    $this->addLog('Key', "Erro ao criar artigo: " . $e->getMessage());
                    Log::error('OrchestrationController: Erro ao criar Article', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->addLog('Key', "Erro inesperado ao criar artigo: " . $e->getMessage());
                Log::error('OrchestrationController: Erro inesperado ao criar Article', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return null;
            }
            
            if (!$article) {
                $this->addLog('Key', "Erro: Falha ao criar artigo (create retornou null)");
                Log::error('OrchestrationController: Article::create retornou null');
                return null;
            }

            $this->addLog('Key', "✅ Concluído com sucesso!");
            $this->addLog('Key', "Título: {$article->title}");
            $this->addLog('Key', "Status: {$article->status}");

            Log::info('OrchestrationController: Agente Key concluído com sucesso', [
                'company_name' => $companyName,
                'symbol' => $symbol,
                'article_id' => $article->id,
                'title' => $article->title,
            ]);

            return $article;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorClass = get_class($e);
            
            $this->addLog('Key', "❌ Erro: " . $errorMessage);
            $this->addLog('Key', "Tipo: {$errorClass}");
            
            Log::error('OrchestrationController: Erro no Agente Key', [
                'error' => $errorMessage,
                'error_class' => $errorClass,
                'trace' => $e->getTraceAsString(),
                'company_name' => $companyName,
                'symbol' => $symbol,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Trata erros de banco de dados (otimizado: usa método helper)
            $this->handleDatabaseError($e, 'Key', 'executeKey');
            
            return null;
        }
    }

    /**
     * Executa Agente PublishNotify
     */
    protected function executePublishNotify(Article $article): void
    {
        $this->addLog('PublishNotify', "Enviando notificação para revisores...");

        try {
            // Simula envio de email (ou envia de verdade se configurado)
            $reviewerEmail = env('REVIEWER_EMAIL');
            
            if ($reviewerEmail) {
                // Aqui você pode implementar o envio real de email
                Log::info('OrchestrationController: Email enviado para revisor', [
                    'email' => $reviewerEmail,
                    'article_id' => $article->id,
                ]);
            }

            // Marca como notificado
            $article->update(['notified_at' => now()]);

            $this->addLog('PublishNotify', "Notificação enviada com sucesso");

        } catch (\Exception $e) {
            $this->addLog('PublishNotify', "Erro: " . $e->getMessage());
            Log::error('OrchestrationController: Erro no Agente PublishNotify', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Garante que o conteúdo é HTML válido
     * parseArticleResponse() já retorna HTML, então apenas valida e limpa
     */
    protected function ensureValidHtml(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $content = trim($content);

        // Remove markdown code blocks se presente (fallback de segurança)
        if (preg_match('/```(?:html)?\s*\n?(.*?)\n?```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        // Verifica se já é HTML válido (contém tags completas)
        if ($this->isValidHtml($content)) {
            return $content;
        }

        // Se não for HTML válido, tenta converter Markdown (fallback)
        // Isso não deveria acontecer se parseArticleResponse() funcionar corretamente
        Log::warning('OrchestrationController: Conteúdo não é HTML válido, tentando converter Markdown', [
            'content_preview' => substr($content, 0, 200),
        ]);

        return $this->convertMarkdownToHtml($content);
    }

    /**
     * Verifica se o conteúdo é HTML válido
     */
    protected function isValidHtml(string $content): bool
    {
        // Verifica se tem tags HTML completas (abertura e fechamento)
        if (!preg_match('/<[a-z][^>]*>.*<\/[a-z]+>/i', $content)) {
            return false;
        }

        // Verifica se não tem Markdown misturado (títulos Markdown)
        if (preg_match('/^#{1,6}\s+/m', $content)) {
            return false; // Tem títulos Markdown
        }

        // Verifica se não tem Markdown de negrito/itálico
        if (preg_match('/\*\*.*?\*\*/', $content) || preg_match('/(?<!\*)\*(?!\*).*?(?<!\*)\*(?!\*)/', $content)) {
            return false; // Tem Markdown de formatação
        }

        return true;
    }

    /**
     * Converte Markdown para HTML (fallback apenas)
     */
    protected function convertMarkdownToHtml(string $content): string
    {
        $html = $content;

        // Títulos
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $html);

        // Negrito
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);

        // Itálico (evita conflito com negrito)
        $html = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $html);

        // Listas (agrupa múltiplos <li> em um único <ul>)
        $html = preg_replace('/^\- (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*?<\/li>(?:\s*<li>.*?<\/li>)*)/s', '<ul>$1</ul>', $html);

        // Parágrafos (linhas vazias separam parágrafos)
        $html = preg_replace('/\n\n+/', '</p><p>', $html);

        // Envolve em parágrafo apenas se não começar com tag HTML
        if (!preg_match('/^<[a-z]/i', trim($html))) {
            $html = '<p>' . $html . '</p>';
        }

        // Quebras de linha (apenas se não for HTML)
        if (!preg_match('/<br\s*\/?>/i', $html)) {
            $html = preg_replace('/\n/', '<br>', $html);
        }

        return $html;
    }

    /**
     * Extrai recomendação do conteúdo
     */
    protected function extractRecommendation(string $content): ?string
    {
        // Tenta extrair recomendação do conteúdo
        if (preg_match('/recomenda[çc][ãa]o[:\s]*(.*?)(?:\.|$)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Processa aprovação ou rejeição do artigo
     * Continua o fluxo após revisão humana
     * 
     * @param Request $request
     * @param int $articleId
     * @return JsonResponse
     */
    public function reviewDecision(Request $request, int $articleId): JsonResponse
    {
        try {
            $decision = $request->input('decision'); // 'approve' ou 'reject'
            $motivoReprovacao = $request->input('motivo_reprovacao');

            if (!in_array($decision, ['approve', 'reject'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Decisão inválida. Use "approve" ou "reject"',
                ], 422);
            }

            $article = Article::findOrFail($articleId);

            if ($article->status !== 'pendente_revisao') {
                return response()->json([
                    'success' => false,
                    'message' => 'Artigo não está pendente de revisão',
                ], 422);
            }

            $this->logs = [];
            $this->addLog('Sistema', "Processando decisão: {$decision}");

            if ($decision === 'approve') {
                // Aprova artigo
                $userId = auth()->id() ?? auth('api')->id() ?? null;
                
                $article->update([
                    'status' => 'aprovado',
                    'reviewed_at' => now(),
                    'reviewed_by' => $userId,
                ]);

                $this->addLog('Sistema', 'Artigo aprovado');

                // Publica artigo
                $article->update([
                    'status' => 'publicado',
                    'published_at' => now(),
                ]);

                $this->addLog('Sistema', "Matéria publicada em /artigos/{$article->id}");

                return response()->json([
                    'success' => true,
                    'status' => 'published',
                    'message' => "[SISTEMA] Matéria publicada em /artigos/{$article->id}",
                    'article' => $article->fresh(),
                    'logs' => $this->logs,
                ]);

            } else {
                // Reprova artigo
                if (!$motivoReprovacao) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Motivo da reprovação é obrigatório',
                    ], 422);
                }

                $userId = auth()->id() ?? auth('api')->id() ?? null;
                
                $article->update([
                    'status' => 'reprovado',
                    'motivo_reprovacao' => $motivoReprovacao,
                    'reviewed_at' => now(),
                    'reviewed_by' => $userId,
                ]);

                $this->addLog('Sistema', 'Artigo reprovado');

                // Executa Agente Cleanup para salvar rascunho
                $this->executeCleanup($article);

                return response()->json([
                    'success' => true,
                    'status' => 'rejected',
                    'message' => '[SISTEMA] Matéria movida para fila de re-análise. Status: Saved for Review',
                    'article' => $article->fresh(),
                    'logs' => $this->logs,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('OrchestrationController: Erro ao processar decisão', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar decisão: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa Agente Cleanup para salvar rascunho descartado
     */
    protected function executeCleanup(Article $article): void
    {
        $this->addLog('Cleanup', "Salvando rascunho descartado do artigo #{$article->id}...");

        try {
            // Busca dados relacionados
            $financialData = $article->financialData;
            $sentimentAnalysis = $article->sentimentAnalysis;

            // Prepara dados completos para salvar
            $draftData = [
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => $article->content,
                    'symbol' => $article->symbol,
                    'status' => $article->status,
                    'motivo_reprovacao' => $article->motivo_reprovacao,
                    'created_at' => $article->created_at?->toIso8601String(),
                    'reviewed_at' => $article->reviewed_at?->toIso8601String(),
                ],
                'financial_data' => $financialData ? [
                    'price' => $financialData->price,
                    'change' => $financialData->change,
                    'volume' => $financialData->volume,
                ] : null,
                'sentiment_analysis' => $sentimentAnalysis ? [
                    'sentiment' => $sentimentAnalysis->sentiment,
                    'sentiment_score' => $sentimentAnalysis->sentiment_score,
                    'news_count' => $sentimentAnalysis->news_count,
                ] : null,
                'saved_at' => now()->toIso8601String(),
            ];

            // Salva como JSON
            $filename = "artigo_{$article->id}_{$article->symbol}_" . now()->format('Y-m-d_His') . '.json';
            $path = storage_path("app/drafts/discarded/{$filename}");

            // Cria diretório se não existir
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($path, json_encode($draftData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->addLog('Cleanup', "Rascunho salvo em: drafts/discarded/{$filename}");

        } catch (\Exception $e) {
            $this->addLog('Cleanup', "Erro: " . $e->getMessage());
            Log::error('OrchestrationController: Erro no Agente Cleanup', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Adiciona log ao array de logs
     */
    protected function addLog(string $agent, string $message): void
    {
        $this->logs[] = [
            'timestamp' => now()->toIso8601String(),
            'agent' => $agent,
            'message' => $message,
        ];
    }

    /**
     * Busca ou cria StockSymbol com tratamento de migrations
     * Otimizado: elimina duplicação de código
     */
    protected function getOrCreateStockSymbol(string $symbol, string $companyName, string $agent = 'Sistema'): ?StockSymbol
    {
        try {
            $stockSymbol = StockSymbol::where('symbol', $symbol)
                ->orWhere('company_name', 'like', "%{$companyName}%")
                ->first();
            
            if ($stockSymbol) {
                return $stockSymbol;
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                $this->addLog($agent, "Tabela stock_symbols não existe. Executando migrations...");
                Artisan::call('migrate', ['--force' => true]);
                $this->addLog($agent, "Migrations executadas. Tentando novamente...");
                
                try {
                    $stockSymbol = StockSymbol::where('symbol', $symbol)
                        ->orWhere('company_name', 'like', "%{$companyName}%")
                        ->first();
                    
                    if ($stockSymbol) {
                        return $stockSymbol;
                    }
                } catch (\Exception $e2) {
                    $this->addLog($agent, "Erro após migrations: " . $e2->getMessage());
                    Log::error("OrchestrationController: Erro ao buscar StockSymbol após migrations", [
                        'error' => $e2->getMessage(),
                        'symbol' => $symbol,
                    ]);
                    return null;
                }
            } else {
                throw $e;
            }
        }

        // Cria se não existir
        try {
            $stockSymbol = StockSymbol::create([
                'symbol' => $symbol,
                'company_name' => $companyName,
                'is_active' => true,
            ]);
            $this->addLog($agent, "StockSymbol criado: {$symbol} ({$companyName})");
            return $stockSymbol;
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                $this->addLog($agent, "Tabela stock_symbols não existe. Executando migrations...");
                Artisan::call('migrate', ['--force' => true]);
                $this->addLog($agent, "Migrations executadas. Tentando criar StockSymbol novamente...");
                
                try {
                    $stockSymbol = StockSymbol::create([
                        'symbol' => $symbol,
                        'company_name' => $companyName,
                        'is_active' => true,
                    ]);
                    $this->addLog($agent, "StockSymbol criado após migrations: {$symbol}");
                    return $stockSymbol;
                } catch (\Exception $e2) {
                    $this->addLog($agent, "Erro ao criar StockSymbol após migrations: " . $e2->getMessage());
                    Log::error("OrchestrationController: Erro ao criar StockSymbol após migrations", [
                        'error' => $e2->getMessage(),
                        'symbol' => $symbol,
                    ]);
                    return null;
                }
            } else {
                $this->addLog($agent, "Erro ao criar StockSymbol: " . $e->getMessage());
                Log::error("OrchestrationController: Erro ao criar StockSymbol", [
                    'error' => $e->getMessage(),
                    'symbol' => $symbol,
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            $this->addLog($agent, "Erro inesperado ao criar StockSymbol: " . $e->getMessage());
            Log::error("OrchestrationController: Erro inesperado ao criar StockSymbol", [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
            ]);
            return null;
        }
    }

    /**
     * Normaliza estrutura de raw_data para formato consistente
     * Otimizado: elimina duplicação de código
     */
    protected function normalizeRawData($rawData, array $analysis): array
    {
        $normalizedRawData = [
            'articles' => [],
            '_analysis' => []
        ];
        
        // Valida se rawData é válido
        if ($rawData === null || (!is_array($rawData) && !is_object($rawData))) {
            return $normalizedRawData;
        }
        
        // Converte objeto para array se necessário
        if (is_object($rawData)) {
            $rawData = (array) $rawData;
        }
        
        // Se raw_data é array de artigos (estrutura antiga), converte
        if (is_array($rawData) && isset($rawData[0]) && is_array($rawData[0])) {
            $normalizedRawData['articles'] = $rawData;
        } elseif (isset($rawData['articles']) && is_array($rawData['articles'])) {
            $normalizedRawData['articles'] = $rawData['articles'];
            if (isset($rawData['_analysis']) && is_array($rawData['_analysis'])) {
                $normalizedRawData['_analysis'] = $rawData['_analysis'];
            }
        } elseif (is_array($rawData) && !empty($rawData)) {
            // Se rawData é um array mas não tem estrutura esperada, tenta usar como artigos
            $normalizedRawData['articles'] = $rawData;
        }
        
        // Adiciona novos dados em _analysis (apenas se analysis for array válido)
        if (is_array($analysis)) {
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
        }
        
        return $normalizedRawData;
    }

    /**
     * Trata erros de banco de dados e executa migrations se necessário
     * Otimizado: elimina duplicação de código
     */
    protected function handleDatabaseError(\Exception $e, string $agent, string $context = ''): void
    {
        if ($e instanceof \Illuminate\Database\QueryException) {
            $this->addLog($agent, "Tentando executar migrations para corrigir erro de banco...");
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->addLog($agent, "Migrations executadas. Tente novamente.");
            } catch (\Exception $migrateError) {
                $this->addLog($agent, "Erro ao executar migrations: " . $migrateError->getMessage());
            }
        }
    }
}

