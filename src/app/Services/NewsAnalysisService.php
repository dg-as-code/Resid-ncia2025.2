<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function __construct()
    {
        $config = config('services.news_api');
        $this->newsApiKey = $config['api_key'] ?? env('NEWS_API_KEY');
        $this->newsApiUrl = $config['base_url'] ?? 'https://newsapi.org/v2/everything';
        $this->timeout = $config['timeout'] ?? 10;
        $this->rateLimit = $config['rate_limit'] ?? 100;
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

        } catch (\Exception $e) {
            Log::error("News Analysis Service error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return $this->getMockNews($symbol, $limit);
        }
    }

    /**
     * Analisa sentimento das notícias
     * 
     * @param array $articles Array de notícias
     * @return array Análise de sentimento
     */
    public function analyzeSentiment(array $articles): array
    {
        if (empty($articles)) {
            return $this->getDefaultSentiment();
        }

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

        return [
            'sentiment' => $sentiment,
            'sentiment_score' => round($avgScore, 4),
            'news_count' => count($articles),
            'positive_count' => $positive,
            'negative_count' => $negative,
            'neutral_count' => $neutral,
            'trending_topics' => implode(', ', $trendingTopics),
            'news_sources' => $sources,
            'raw_data' => $articles,
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

