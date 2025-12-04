<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\GeminiResponseService;

/**
 * Service para análise de notícias e sentimento
 * 
 * Integra com APIs de notícias como NewsAPI, Google News, etc.
 * E faz análise de sentimento básica.
 */
class NewsAnalysisService
{
    protected $newsApiKey;
    protected $newsApiUrl;
    protected $timeout;
    protected $rateLimit;
    protected $geminiService;

    public function __construct()
    {
        $config = config('services.news_api');
        $this->newsApiKey = $config['api_key'] ?? env('NEWS_API_KEY');
        $this->newsApiUrl = $config['base_url'] ?? 'https://newsapi.org/v2/everything';
        $this->timeout = $config['timeout'] ?? 10;
        $this->rateLimit = $config['rate_limit'] ?? 100;
        $this->geminiService = new GeminiResponseService();
    }

    /**
     * Busca notícias sobre uma empresa/ação
     * 
     * @param string $symbol Símbolo da ação
     * @param string $companyName Nome da empresa
     * @param int $limit Limite de notícias
     * @return array
     */
    public function searchNews(string $symbol, string $companyName = '', int $limit = 20): array
    {
        try {
            $query = $companyName ?: $symbol;
            
            // Se não tiver API key, retorna dados mockados para desenvolvimento
            if (!$this->newsApiKey) {
                Log::warning('NEWS_API_KEY não configurada. Usando dados mockados.');
                return $this->getMockNews($symbol, $limit);
            }

            // Cache por 15 minutos para reduzir chamadas à API
            $cacheKey = "news:{$query}:{$limit}:" . date('Y-m-d-H-i');
            
            return Cache::remember($cacheKey, 900, function () use ($query, $symbol, $limit) {
                $response = Http::timeout($this->timeout)->get($this->newsApiUrl, [
                    'q' => $query,
                    'language' => 'pt',
                    'sortBy' => 'publishedAt',
                    'pageSize' => $limit,
                    'apiKey' => $this->newsApiKey,
                ]);

                if (!$response->successful()) {
                    Log::warning("News API error for {$symbol}", [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return $this->getMockNews($symbol, $limit);
                }

                $data = $response->json();
                return $data['articles'] ?? [];
            });

        } catch (\Exception $e) {
            Log::error("News Analysis Service error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return $this->getMockNews($symbol, $limit);
        }
    }

    /**
     * Analisa sentimento das notícias com LLM para análise profunda
     * 
     * @param array $articles Array de notícias
     * @param string $symbol Símbolo da ação
     * @param string $companyName Nome da empresa
     * @param array $financialData Dados financeiros (opcional)
     * @return array Análise de sentimento enriquecida
     */
    public function analyzeSentiment(array $articles, string $symbol = '', string $companyName = '', array $financialData = []): array
    {
        if (empty($articles)) {
            return $this->getDefaultSentiment();
        }

        // Análise básica de sentimento (fallback)
        $basicAnalysis = $this->analyzeSentimentBasic($articles);
        
        // Análise avançada com LLM
        $llmAnalysis = [];
        if ($this->geminiService && method_exists($this->geminiService, 'isConfigured') && $this->geminiService->isConfigured()) {
            $llmAnalysis = $this->analyzeSentimentWithLLM($articles, $symbol, $companyName, $financialData);
        }
        
        // Análise profunda de métricas de marca (presença e percepção)
        $brandMetrics = [];
        if ($this->geminiService && method_exists($this->geminiService, 'isConfigured') && $this->geminiService->isConfigured()) {
            $brandMetrics = $this->analyzeBrandPerceptionMetrics($articles, $symbol, $companyName, $financialData);
        }
        
        // Combina análises
        $combinedAnalysis = $this->combineAnalyses($basicAnalysis, $llmAnalysis, $articles);
        
        // Adiciona métricas de marca
        return array_merge($combinedAnalysis, $brandMetrics);
    }

    /**
     * Análise básica de sentimento (método original)
     */
    protected function analyzeSentimentBasic(array $articles): array
    {
        $positive = 0;
        $negative = 0;
        $neutral = 0;
        $totalScore = 0;
        $trendingTopics = [];
        $sources = [];

        foreach ($articles as $article) {
            $title = $article['title'] ?? '';
            $description = $article['description'] ?? '';
            $content = strtolower($title . ' ' . $description);
            
            // Análise simples de sentimento baseada em palavras-chave
            $score = $this->calculateSentimentScore($content);
            $totalScore += $score;

            if ($score > 0.1) {
                $positive++;
            } elseif ($score < -0.1) {
                $negative++;
            } else {
                $neutral++;
            }

            // Coleta fontes
            $source = $article['source']['name'] ?? 'Desconhecido';
            if (!in_array($source, $sources)) {
                $sources[] = $source;
            }

            // Extrai palavras-chave (implementação simples)
            $keywords = $this->extractKeywords($content);
            $trendingTopics = array_merge($trendingTopics, $keywords);
        }

        $avgScore = count($articles) > 0 ? $totalScore / count($articles) : 0;
        
        $sentiment = 'neutral';
        if ($avgScore > 0.1) {
            $sentiment = 'positive';
        } elseif ($avgScore < -0.1) {
            $sentiment = 'negative';
        }

        // Remove duplicatas e limita trending topics
        $trendingTopics = array_unique($trendingTopics);
        $trendingTopics = array_slice($trendingTopics, 0, 10);
        
        // Garante que trending_topics seja array (não string)
        if (!is_array($trendingTopics)) {
            $trendingTopics = [];
        }

        return [
            'sentiment' => $sentiment,
            'sentiment_score' => round($avgScore, 4),
            'positive_count' => $positive,
            'negative_count' => $negative,
            'neutral_count' => $neutral,
            'trending_topics' => $trendingTopics,
            'news_sources' => $sources,
        ];
    }

    /**
     * Análise avançada de sentimento usando LLM (Gemini)
     */
    protected function analyzeSentimentWithLLM(array $articles, string $symbol, string $companyName, array $financialData = []): array
    {
        try {
            // Verifica se Gemini está configurado
            if (!$this->geminiService || !method_exists($this->geminiService, 'isConfigured')) {
                Log::warning('GeminiResponseService não disponível. Usando análise básica.');
                return [];
            }
            
            if (!$this->geminiService->isConfigured()) {
                Log::warning('Gemini API não configurada. Usando análise básica.');
                return [];
            }

            // Prepara contexto das notícias
            $newsContext = $this->prepareNewsContext($articles);
            
            // Prepara contexto financeiro
            $financialContext = $this->prepareFinancialContext($financialData);
            
            // Constrói prompt para análise profunda
            $prompt = $this->buildMarketAnalysisPrompt($symbol, $companyName, $newsContext, $financialContext);
            
            // Chama LLM para análise
            $result = $this->geminiService->generateResponse($prompt, [
                'temperature' => 0.3, // Mais determinístico para análise
                'max_tokens' => 2048,
                'response_format' => 'json',
                'system_instruction' => 'Você é um analista financeiro experiente especializado em análise de mercado e macroeconomia. Forneça análises precisas, objetivas e baseadas em dados.',
            ]);

            if (!$result['success']) {
                Log::warning('Erro na análise LLM do Agente Pedro', [
                    'error' => $result['error'] ?? 'Erro desconhecido'
                ]);
                return [];
            }

            // Parse da resposta JSON
            $analysis = $this->parseLLMAnalysis($result['content'] ?? '');
            
            // Valida estrutura básica
            if (empty($analysis) || !is_array($analysis)) {
                Log::warning('Análise LLM retornou estrutura inválida');
                return [];
            }
            
            return $analysis;

        } catch (\Exception $e) {
            Log::error('Erro na análise LLM do Agente Pedro', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500) // Limita tamanho do trace
            ]);
            return [];
        }
    }

    /**
     * Prepara contexto das notícias para análise LLM
     */
    protected function prepareNewsContext(array $articles): string
    {
        $context = "Notícias recentes:\n\n";
        
        foreach (array_slice($articles, 0, 15) as $index => $article) {
            $title = $article['title'] ?? 'Sem título';
            $description = $article['description'] ?? '';
            $source = $article['source']['name'] ?? 'Fonte desconhecida';
            $publishedAt = $article['publishedAt'] ?? '';
            
            $context .= ($index + 1) . ". [{$source}] {$title}\n";
            if ($description) {
                $context .= "   {$description}\n";
            }
            if ($publishedAt) {
                $context .= "   Publicado em: {$publishedAt}\n";
            }
            $context .= "\n";
        }
        
        return $context;
    }

    /**
     * Prepara contexto financeiro para análise
     */
    protected function prepareFinancialContext(array $financialData): string
    {
        if (empty($financialData)) {
            return "Dados financeiros não disponíveis.";
        }

        $context = "Dados Financeiros Atuais:\n";
        $context .= "- Preço: " . ($financialData['price'] ?? 'N/A') . "\n";
        $context .= "- Variação: " . ($financialData['change_percent'] ?? 'N/A') . "%\n";
        $context .= "- Volume: " . ($financialData['volume'] ?? 'N/A') . "\n";
        $context .= "- Market Cap: " . ($financialData['market_cap'] ?? 'N/A') . "\n";
        $context .= "- P/L: " . ($financialData['pe_ratio'] ?? 'N/A') . "\n";
        $context .= "- Dividend Yield: " . ($financialData['dividend_yield'] ?? 'N/A') . "%\n";
        $context .= "- Alta 52 semanas: " . ($financialData['high_52w'] ?? 'N/A') . "\n";
        $context .= "- Baixa 52 semanas: " . ($financialData['low_52w'] ?? 'N/A') . "\n";
        
        return $context;
    }

    /**
     * Constrói prompt para análise de mercado e macroeconomia
     */
    protected function buildMarketAnalysisPrompt(string $symbol, string $companyName, string $newsContext, string $financialContext): string
    {
        return <<<PROMPT
Você é um analista financeiro experiente especializado em análise de mercado e macroeconomia.

Analise o contexto abaixo e forneça uma análise completa em formato JSON com a seguinte estrutura:

{
  "sentiment": "positive|negative|neutral",
  "sentiment_score": -1.0 a 1.0,
  "market_analysis": {
    "overall_trend": "descrição do trend geral do mercado",
    "key_drivers": ["driver1", "driver2", "driver3"],
    "risk_factors": ["risco1", "risco2"],
    "opportunities": ["oportunidade1", "oportunidade2"]
  },
  "macroeconomic_analysis": {
    "economic_context": "contexto macroeconômico relevante",
    "sector_performance": "performance do setor",
    "market_indicators": "indicadores de mercado relevantes",
    "inflation_impact": "impacto da inflação",
    "interest_rate_impact": "impacto das taxas de juros",
    "currency_impact": "impacto cambial se relevante"
  },
  "trending_topics": ["tópico1", "tópico2", "tópico3"],
  "key_insights": ["insight1", "insight2", "insight3"],
  "recommendation": "breve recomendação baseada na análise"
}

Empresa: {$companyName} ({$symbol})

{$financialContext}

{$newsContext}

Forneça uma análise profunda, objetiva e baseada em dados reais. Considere:
- Contexto macroeconômico atual
- Tendências de mercado
- Performance do setor
- Indicadores econômicos relevantes
- Impacto de políticas econômicas
- Análise técnica e fundamentalista

Retorne APENAS o JSON, sem markdown ou texto adicional.
PROMPT;
    }

    /**
     * Faz parse da análise retornada pela LLM
     */
    protected function parseLLMAnalysis(string $content): array
    {
        // Remove markdown code blocks se existirem
        $content = preg_replace('/```(?:json)?\s*\n?(.*?)\n?```/s', '$1', $content);
        $content = trim($content);
        
        // Tenta fazer parse do JSON
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Erro ao fazer parse da análise LLM', [
                'error' => json_last_error_msg(),
                'content' => substr($content, 0, 500)
            ]);
            return [];
        }
        
        return $data;
    }

    /**
     * Combina análise básica com análise LLM
     */
    protected function combineAnalyses(array $basicAnalysis, array $llmAnalysis, array $articles): array
    {
        // Se não há análise LLM, retorna análise básica
        if (empty($llmAnalysis)) {
            return [
                'sentiment' => $basicAnalysis['sentiment'],
                'sentiment_score' => $basicAnalysis['sentiment_score'],
                'news_count' => count($articles),
                'positive_count' => $basicAnalysis['positive_count'],
                'negative_count' => $basicAnalysis['negative_count'],
                'neutral_count' => $basicAnalysis['neutral_count'],
                'trending_topics' => implode(', ', $basicAnalysis['trending_topics']),
                'news_sources' => $basicAnalysis['news_sources'],
                'raw_data' => $articles,
            ];
        }

        // Combina sentimento (prioriza LLM se disponível)
        $sentiment = $llmAnalysis['sentiment'] ?? $basicAnalysis['sentiment'];
        $sentimentScore = $llmAnalysis['sentiment_score'] ?? $basicAnalysis['sentiment_score'];
        
        // Combina trending topics
        $trendingTopics = $llmAnalysis['trending_topics'] ?? $basicAnalysis['trending_topics'];
        if (is_array($trendingTopics)) {
            $trendingTopics = implode(', ', $trendingTopics);
        }

        return [
            'sentiment' => $sentiment,
            'sentiment_score' => round((float)$sentimentScore, 4),
            'news_count' => count($articles),
            'positive_count' => $basicAnalysis['positive_count'],
            'negative_count' => $basicAnalysis['negative_count'],
            'neutral_count' => $basicAnalysis['neutral_count'],
            'trending_topics' => $trendingTopics,
            'news_sources' => $basicAnalysis['news_sources'],
            'raw_data' => $articles,
            // Dados enriquecidos da LLM
            'market_analysis' => $llmAnalysis['market_analysis'] ?? null,
            'macroeconomic_analysis' => $llmAnalysis['macroeconomic_analysis'] ?? null,
            'key_insights' => $llmAnalysis['key_insights'] ?? null,
            'recommendation' => $llmAnalysis['recommendation'] ?? null,
        ];
    }

    /**
     * Calcula score de sentimento simples
     * 
     * @param string $content
     * @return float
     */
    protected function calculateSentimentScore(string $content): float
    {
        $positiveWords = ['cresce', 'alta', 'ganho', 'lucro', 'positivo', 'subiu', 'melhora', 'expansão', 'crescimento', 'sucesso', 'vitória'];
        $negativeWords = ['queda', 'perda', 'prejuízo', 'negativo', 'caiu', 'decresce', 'crise', 'problema', 'risco', 'queda', 'derrota'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($content, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($content, $word);
        }

        if ($positiveCount + $negativeCount == 0) {
            return 0;
        }

        return ($positiveCount - $negativeCount) / ($positiveCount + $negativeCount);
    }

    /**
     * Extrai palavras-chave simples
     * 
     * @param string $content
     * @return array
     */
    protected function extractKeywords(string $content): array
    {
        // Remove stop words e palavras muito curtas
        $stopWords = ['o', 'a', 'de', 'do', 'da', 'em', 'no', 'na', 'para', 'com', 'que', 'e', 'ou', 'se', 'um', 'uma', 'por', 'mais'];
        $words = preg_split('/\s+/', $content);
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 4 && !in_array($word, $stopWords);
        });

        // Conta frequência e retorna as mais comuns
        $frequency = array_count_values($words);
        arsort($frequency);
        
        return array_keys(array_slice($frequency, 0, 10));
    }

    /**
     * Retorna notícias mockadas para desenvolvimento
     * 
     * @param string $symbol
     * @param int $limit
     * @return array
     */
    protected function getMockNews(string $symbol, int $limit): array
    {
        return [
            [
                'title' => "Análise: {$symbol} mostra sinais positivos no mercado",
                'description' => "Especialistas indicam crescimento para {$symbol}",
                'source' => ['name' => 'Financial News'],
                'publishedAt' => now()->toIso8601String(),
            ]
        ];
    }

    /**
     * Analisa métricas de presença e percepção da marca usando LLM
     * 
     * @param array $articles Array de notícias
     * @param string $symbol Símbolo da ação
     * @param string $companyName Nome da empresa
     * @param array $financialData Dados financeiros (opcional)
     * @return array Métricas de marca
     */
    protected function analyzeBrandPerceptionMetrics(array $articles, string $symbol, string $companyName, array $financialData = []): array
    {
        try {
            if (!$this->geminiService || !method_exists($this->geminiService, 'isConfigured') || !$this->geminiService->isConfigured()) {
                Log::debug('Gemini não configurado para análise de métricas de marca');
                return [];
            }

            // Prepara contexto das notícias
            $newsContext = $this->prepareNewsContext($articles);
            
            // Prepara contexto financeiro
            $financialContext = $this->prepareFinancialContext($financialData);
            
            // Constrói prompt para análise de marca
            $prompt = $this->buildBrandPerceptionPrompt($symbol, $companyName, $newsContext, $financialContext, $articles);
            
            // Limita tamanho do prompt para evitar problemas (máximo ~30k caracteres)
            if (strlen($prompt) > 30000) {
                Log::warning('Prompt muito longo para análise de métricas de marca, truncando', [
                    'original_length' => strlen($prompt)
                ]);
                $prompt = substr($prompt, 0, 30000) . "\n\n[Prompt truncado devido ao tamanho]";
            }
            
            // Chama LLM para análise
            $result = $this->geminiService->generateResponse($prompt, [
                'temperature' => 0.4, // Balanceado para análise estratégica
                'max_tokens' => 3072, // Mais tokens para análise detalhada
                'response_format' => 'json',
                'system_instruction' => 'Você é um analista especializado em métricas de presença e percepção de marca. Forneça análises estratégicas, objetivas e acionáveis baseadas em dados reais.',
            ]);

            if (!$result['success']) {
                Log::warning('Erro na análise de métricas de marca', [
                    'error' => $result['error'] ?? 'Erro desconhecido',
                    'symbol' => $symbol
                ]);
                return [];
            }

            // Parse da resposta JSON
            $content = $result['content'] ?? '';
            
            // Se content é array (já parseado), usa diretamente
            if (is_array($content)) {
                $metrics = $content;
            } else {
                $metrics = $this->parseLLMAnalysis($content);
            }
            
            // Valida estrutura básica
            if (empty($metrics) || !is_array($metrics)) {
                Log::warning('Análise de métricas de marca retornou estrutura inválida', [
                    'content_preview' => substr(is_string($content) ? $content : json_encode($content), 0, 200)
                ]);
                return [];
            }
            
            return $metrics;

        } catch (\Exception $e) {
            Log::error('Erro na análise de métricas de marca', [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            // Retorna vazio em caso de erro para não quebrar o fluxo
            return [];
        }
    }

    /**
     * Constrói prompt para análise de presença e percepção da marca
     * Inclui dados digitais, comportamentais e geração de insights estratégicos
     */
    protected function buildBrandPerceptionPrompt(string $symbol, string $companyName, string $newsContext, string $financialContext, array $articles): string
    {
        $totalMentions = count($articles);
        $dates = array_map(function($article) {
            return $article['publishedAt'] ?? '';
        }, $articles);
        
        return <<<PROMPT
Você é um analista especializado em métricas de presença e percepção de marca, análise comportamental do público e geração de insights estratégicos acionáveis.

Empresa: {$companyName} (Ticker: {$symbol})

{$financialContext}

Notícias e Menções ({$totalMentions} itens):
{$newsContext}

Com base nessas informações, forneça uma análise COMPLETA e ESTRATÉGICA em formato JSON com a seguinte estrutura:

{
  "total_mentions": {$totalMentions},
  "mentions_peak": {
    "value": número do pico de menções,
    "date": "data do pico",
    "reason": "explicação do que causou o pico (evento positivo, campanha, crise, reclamações em massa, etc)"
  },
  "mentions_timeline": [
    {"date": "YYYY-MM-DD", "count": número, "trend": "up|down|stable"}
  ],
  "sentiment_breakdown": {
    "positive_percentage": porcentagem,
    "negative_percentage": porcentagem,
    "neutral_percentage": porcentagem,
    "dominant_emotions": ["emoção1", "emoção2", "emoção3"],
    "sentiment_balance": "análise do equilíbrio entre sentimentos e o que isso revela sobre a reputação"
  },
  "digital_data": {
    "volume_mentions": {
      "total": número total de menções,
      "relevance": "alta|média|baixa",
      "notoriety": "análise da relevância e notoriedade da marca no mercado"
    },
    "sentiment_public": {
      "positive": porcentagem,
      "negative": porcentagem,
      "neutral": porcentagem,
      "interpretation": "interpretação da percepção real da marca pelo público"
    },
    "engagement": {
      "clicks_estimated": número estimado,
      "shares_estimated": número estimado,
      "comments_estimated": número estimado,
      "time_spent_estimated": "tempo médio estimado",
      "engagement_score": 0-100,
      "interpretation": "interpretação do nível de interesse e profundidade das interações"
    },
    "reach": {
      "organic_reach_estimated": número estimado,
      "paid_reach_estimated": número estimado,
      "total_reach_estimated": número estimado,
      "reach_effectiveness": "análise da efetividade do alcance orgânico vs pago"
    }
  },
  "behavioral_data": {
    "purchase_intentions": {
      "level": "alto|médio|baixo",
      "indicators": ["indicador1", "indicador2"],
      "trend": "crescendo|estável|diminuindo",
      "interpretation": "análise das intenções de compra do público baseada nas menções e contexto"
    },
    "complaints": {
      "count": número estimado,
      "main_categories": [
        {
          "category": "categoria (ex: atendimento, produto, preço)",
          "count": número,
          "severity": "alta|média|baixa"
        }
      ],
      "trend": "crescendo|estável|diminuindo",
      "interpretation": "análise das reclamações identificadas"
    },
    "social_feedback": {
      "positive_feedback_count": número estimado,
      "negative_feedback_count": número estimado,
      "neutral_feedback_count": número estimado,
      "main_topics": ["tópico1", "tópico2"],
      "interpretation": "análise do feedback em redes sociais identificado nas menções"
    },
    "product_reviews": {
      "average_rating_estimated": 0-5,
      "review_count_estimated": número,
      "positive_reviews_percentage": porcentagem,
      "negative_reviews_percentage": porcentagem,
      "main_concerns": ["preocupação1", "preocupação2"],
      "interpretation": "análise das avaliações de produtos identificadas nas menções"
    }
  },
  "main_themes": [
    {
      "theme": "nome do tema (ex: atendimento, preço, qualidade, experiência do usuário)",
      "frequency": número de menções,
      "impact": "alto|médio|baixo",
      "sentiment": "positive|negative|neutral",
      "explanation": "por que este tema está gerando impacto"
    }
  ],
  "engagement_metrics": {
    "estimated_reach": número estimado,
    "engagement_score": 0-100,
    "relevance_score": 0-100,
    "trust_score": 0-100,
    "interpretation": "interpretação do nível de interesse, relevância e confiança do público"
  },
  "investor_confidence": {
    "retention_indicators": "indicadores de retenção",
    "loyalty_indicators": "indicadores de lealdade",
    "satisfaction_indicators": "indicadores de satisfação",
    "financial_confidence": "confiança financeira baseada em dados",
    "overall_confidence_score": 0-100,
    "interpretation": "interpretação do nível de confiança geral na empresa"
  },
  "brand_perception": {
    "overall_perception": "positiva|negativa|neutra|mista",
    "key_strengths": ["força1", "força2"],
    "key_weaknesses": ["fraqueza1", "fraqueza2"],
    "reputation_status": "excelente|boa|regular|ruim|crítica",
    "perception_trend": "melhorando|estável|piorando"
  },
  "strategic_insights": [
    {
      "insight": "insight estratégico específico e acionável (ex: 'O público está mais sensível ao preço', 'O concorrente X está ganhando share', 'Há tendência de crescimento em tema Y', 'A satisfação do cliente está caindo')",
      "category": "preço|concorrência|tendência|satisfação|custos|investimento",
      "priority": "alta|média|baixa",
      "evidence": "evidências que suportam o insight",
      "recommendation": "recomendação específica e acionável"
    }
  ],
  "cost_optimization": {
    "areas_to_cut": [
      {
        "area": "área onde cortar custos",
        "potential_savings": "estimativa de economia",
        "impact": "baixo|médio|alto impacto no negócio",
        "recommendation": "recomendação específica"
      }
    ],
    "areas_to_invest": [
      {
        "area": "área onde investir",
        "potential_return": "estimativa de retorno",
        "priority": "alta|média|baixa",
        "recommendation": "recomendação específica de investimento"
      }
    ],
    "strategic_recommendation": "recomendação estratégica sobre onde cortar custos ou investir baseada na análise completa"
  },
  "actionable_insights": [
    {
      "insight": "insight acionável específico",
      "priority": "alta|média|baixa",
      "action": "ação recomendada"
    }
  ],
  "improvement_opportunities": [
    {
      "opportunity": "oportunidade de melhoria",
      "impact": "alto|médio|baixo",
      "feasibility": "alta|média|baixa",
      "recommendation": "recomendação específica"
    }
  ],
  "risk_alerts": [
    {
      "risk": "risco ou tendência emergente identificada",
      "severity": "crítica|alta|média|baixa",
      "trend": "crescendo|estável|diminuindo",
      "recommendation": "recomendação para mitigação"
    }
  ],
  "strategic_analysis": "Análise estratégica completa e objetiva, incluindo: 1) Causas possíveis dos padrões identificados, 2) Oportunidades de melhoria, 3) Alertas sobre riscos ou tendências emergentes, 4) Recomendações sobre onde cortar custos ou investir. Seja claro, estratégico, objetivo e acionável."
}

IMPORTANTE: 
- Os insights em "strategic_insights" devem ser específicos e acionáveis, como: "O público está mais sensível ao preço", "O concorrente X está ganhando share", "Há tendência de crescimento em tema Y", "A satisfação do cliente está caindo"
- A seção "cost_optimization" deve fornecer recomendações claras sobre onde cortar custos ou investir
- Use dados reais das notícias e contexto financeiro para fundamentar todas as análises
- Seja objetivo, estratégico e focado em ações práticas

Retorne APENAS o JSON, sem markdown ou texto adicional.
PROMPT;
    }

    /**
     * Retorna sentimento padrão (neutro)
     * 
     * @return array
     */
    protected function getDefaultSentiment(): array
    {
        return [
            'sentiment' => 'neutral',
            'sentiment_score' => 0,
            'news_count' => 0,
            'positive_count' => 0,
            'negative_count' => 0,
            'neutral_count' => 0,
            'trending_topics' => null,
            'news_sources' => [],
            'raw_data' => [],
        ];
    }
}

