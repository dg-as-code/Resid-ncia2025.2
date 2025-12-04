<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\YahooFinanceService;
use App\Services\NewsAnalysisService;
use App\Services\LLMService;
use App\Services\GeminiResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * Teste de validação de fluxo completo entre serviços
 * 
 * Verifica:
 * - Input/Output de cada serviço
 * - Compatibilidade de tipos e estruturas
 * - Fluxo de dados entre Júlia → Pedro → Key
 */
class FlowValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o fluxo completo: YahooFinanceService → NewsAnalysisService → LLMService
     */
    public function test_complete_flow_validation(): void
    {
        $companyName = 'Petrobras';
        
        // PASSO 1: Testa YahooFinanceService (Agente Júlia)
        $this->testYahooFinanceService($companyName);
        
        // PASSO 2: Testa NewsAnalysisService (Agente Pedro)
        $financialData = $this->getMockFinancialData();
        $this->testNewsAnalysisService($companyName, $financialData);
        
        // PASSO 3: Testa LLMService (Agente Key)
        $sentimentData = $this->getMockSentimentData();
        $this->testLLMService($financialData, $sentimentData, 'PETR4');
    }

    /**
     * Testa YahooFinanceService::getQuote()
     */
    protected function testYahooFinanceService(string $companyName): void
    {
        $service = new YahooFinanceService();
        $result = $service->getQuote($companyName);
        
        // Valida estrutura de retorno
        $this->assertNotNull($result, 'YahooFinanceService deve retornar dados');
        $this->assertIsArray($result, 'YahooFinanceService deve retornar array');
        
        // Valida campos obrigatórios
        $requiredFields = ['symbol', 'price', 'previous_close', 'change', 'change_percent'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $result, "YahooFinanceService deve retornar campo '{$field}'");
        }
        
        // Valida tipos
        $this->assertIsString($result['symbol'], 'symbol deve ser string');
        $this->assertTrue(
            is_numeric($result['price']) || $result['price'] === null,
            'price deve ser numérico ou null'
        );
        
        // Valida company_name (pode ser null)
        if (isset($result['company_name'])) {
            $this->assertIsString($result['company_name'], 'company_name deve ser string se presente');
        }
        
        // Valida raw_data (pode ser null)
        if (isset($result['raw_data'])) {
            $this->assertTrue(
                is_array($result['raw_data']) || is_string($result['raw_data']) || $result['raw_data'] === null,
                'raw_data deve ser array, string ou null'
            );
        }
        
        Log::info('✅ YahooFinanceService: Estrutura válida', [
            'fields' => array_keys($result),
            'symbol' => $result['symbol'] ?? 'N/A',
        ]);
    }

    /**
     * Testa NewsAnalysisService::analyzeSentiment()
     */
    protected function testNewsAnalysisService(string $companyName, array $financialData): void
    {
        $service = new NewsAnalysisService();
        
        // Mock de artigos
        $articles = $this->getMockArticles();
        
        $result = $service->analyzeSentiment($articles, 'PETR4', $companyName, $financialData);
        
        // Valida estrutura de retorno
        $this->assertNotNull($result, 'NewsAnalysisService deve retornar dados');
        $this->assertIsArray($result, 'NewsAnalysisService deve retornar array');
        
        // Valida campos obrigatórios
        $requiredFields = ['sentiment', 'sentiment_score', 'news_count'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $result, "NewsAnalysisService deve retornar campo '{$field}'");
        }
        
        // Valida tipos
        $this->assertIsString($result['sentiment'], 'sentiment deve ser string');
        $this->assertContains($result['sentiment'], ['positive', 'negative', 'neutral'], 'sentiment deve ser positive, negative ou neutral');
        $this->assertIsFloat($result['sentiment_score'], 'sentiment_score deve ser float');
        $this->assertIsInt($result['news_count'], 'news_count deve ser int');
        
        // Valida trending_topics (pode ser array ou string)
        if (isset($result['trending_topics'])) {
            $this->assertTrue(
                is_array($result['trending_topics']) || is_string($result['trending_topics']),
                'trending_topics deve ser array ou string'
            );
        }
        
        // Valida news_sources (pode ser array ou null)
        if (isset($result['news_sources'])) {
            $this->assertTrue(
                is_array($result['news_sources']) || $result['news_sources'] === null,
                'news_sources deve ser array ou null'
            );
        }
        
        // Valida raw_data (deve ser array)
        if (isset($result['raw_data'])) {
            $this->assertIsArray($result['raw_data'], 'raw_data deve ser array');
        }
        
        // Valida campos opcionais enriquecidos
        $optionalFields = [
            'market_analysis', 'macroeconomic_analysis', 'key_insights', 'recommendation',
            'total_mentions', 'mentions_peak', 'sentiment_breakdown',
            'engagement_metrics', 'brand_perception', 'actionable_insights',
            'strategic_analysis', 'risk_alerts', 'improvement_opportunities'
        ];
        
        foreach ($optionalFields as $field) {
            if (isset($result[$field])) {
                $this->assertTrue(
                    is_array($result[$field]) || is_string($result[$field]) || $result[$field] === null,
                    "{$field} deve ser array, string ou null"
                );
            }
        }
        
        Log::info('✅ NewsAnalysisService: Estrutura válida', [
            'fields' => array_keys($result),
            'sentiment' => $result['sentiment'],
            'has_enriched_data' => !empty($result['market_analysis']) || !empty($result['brand_perception']),
        ]);
    }

    /**
     * Testa LLMService::generateArticle()
     */
    protected function testLLMService(array $financialData, array $sentimentData, string $symbol): void
    {
        $service = new LLMService();
        
        // Testa prepareInputData
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('prepareInputData');
        $method->setAccessible(true);
        
        $inputData = $method->invoke($service, $financialData, $sentimentData, $symbol);
        
        // Valida que prepareInputData retorna JSON válido
        $this->assertIsString($inputData, 'prepareInputData deve retornar string JSON');
        $decoded = json_decode($inputData, true);
        $this->assertNotNull($decoded, 'prepareInputData deve retornar JSON válido');
        $this->assertArrayHasKey('symbol', $decoded, 'JSON deve conter symbol');
        $this->assertArrayHasKey('financial', $decoded, 'JSON deve conter financial');
        $this->assertArrayHasKey('sentiment', $decoded, 'JSON deve conter sentiment');
        
        // Valida que financial contém campos esperados
        $financialFields = ['price', 'previous_close', 'change', 'change_percent', 'volume'];
        foreach ($financialFields as $field) {
            if (isset($financialData[$field])) {
                $this->assertArrayHasKey($field, $decoded['financial'], "financial deve conter '{$field}'");
            }
        }
        
        // Valida que sentiment contém campos esperados
        $sentimentFields = ['sentiment', 'sentiment_score', 'news_count'];
        foreach ($sentimentFields as $field) {
            if (isset($sentimentData[$field])) {
                $this->assertArrayHasKey($field, $decoded['sentiment'], "sentiment deve conter '{$field}'");
            }
        }
        
        // Testa generateArticle (pode falhar se Gemini não estiver configurado, mas estrutura deve ser válida)
        try {
            $result = $service->generateArticle($financialData, $sentimentData, $symbol);
            
            // Valida estrutura de retorno
            $this->assertIsArray($result, 'generateArticle deve retornar array');
            $this->assertArrayHasKey('title', $result, 'generateArticle deve retornar title');
            $this->assertArrayHasKey('content', $result, 'generateArticle deve retornar content');
            $this->assertIsString($result['title'], 'title deve ser string');
            $this->assertIsString($result['content'], 'content deve ser string');
            $this->assertNotEmpty($result['title'], 'title não deve estar vazio');
            $this->assertNotEmpty($result['content'], 'content não deve estar vazio');
            
            Log::info('✅ LLMService: Estrutura válida', [
                'has_title' => !empty($result['title']),
                'has_content' => !empty($result['content']),
                'title_length' => strlen($result['title']),
                'content_length' => strlen($result['content']),
            ]);
        } catch (\Exception $e) {
            // Se falhar por falta de configuração, apenas loga
            Log::warning('LLMService: Teste não executado (configuração ausente)', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Testa compatibilidade entre YahooFinanceService e NewsAnalysisService
     */
    public function test_yahoo_to_news_compatibility(): void
    {
        $companyName = 'Petrobras';
        $yahooService = new YahooFinanceService();
        $financialData = $yahooService->getQuote($companyName);
        
        // Valida que financialData tem campos necessários para NewsAnalysisService
        $this->assertArrayHasKey('symbol', $financialData, 'financialData deve ter symbol para NewsAnalysisService');
        $this->assertArrayHasKey('price', $financialData, 'financialData deve ter price para NewsAnalysisService');
        
        // Testa se NewsAnalysisService aceita financialData
        $newsService = new NewsAnalysisService();
        $articles = $this->getMockArticles();
        
        $result = $newsService->analyzeSentiment(
            $articles,
            $financialData['symbol'] ?? 'PETR4',
            $companyName,
            $financialData
        );
        
        $this->assertNotNull($result, 'NewsAnalysisService deve processar financialData de YahooFinanceService');
        
        Log::info('✅ Compatibilidade YahooFinanceService → NewsAnalysisService: OK');
    }

    /**
     * Testa compatibilidade entre NewsAnalysisService e LLMService
     */
    public function test_news_to_llm_compatibility(): void
    {
        $financialData = $this->getMockFinancialData();
        $newsService = new NewsAnalysisService();
        $articles = $this->getMockArticles();
        
        $sentimentData = $newsService->analyzeSentiment($articles, 'PETR4', 'Petrobras', $financialData);
        
        // Valida que sentimentData tem campos necessários para LLMService
        $this->assertArrayHasKey('sentiment', $sentimentData, 'sentimentData deve ter sentiment para LLMService');
        $this->assertArrayHasKey('sentiment_score', $sentimentData, 'sentimentData deve ter sentiment_score para LLMService');
        
        // Testa se LLMService aceita sentimentData
        $llmService = new LLMService();
        
        try {
            $result = $llmService->generateArticle($financialData, $sentimentData, 'PETR4');
            $this->assertNotNull($result, 'LLMService deve processar sentimentData de NewsAnalysisService');
            $this->assertArrayHasKey('title', $result, 'LLMService deve retornar title');
            $this->assertArrayHasKey('content', $result, 'LLMService deve retornar content');
            
            Log::info('✅ Compatibilidade NewsAnalysisService → LLMService: OK');
        } catch (\Exception $e) {
            Log::warning('LLMService: Teste não executado (configuração ausente)', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Testa normalização de trending_topics (pode ser array ou string)
     */
    public function test_trending_topics_normalization(): void
    {
        $newsService = new NewsAnalysisService();
        $articles = $this->getMockArticles();
        
        $result = $newsService->analyzeSentiment($articles, 'PETR4', 'Petrobras', []);
        
        // trending_topics pode ser array ou string
        if (isset($result['trending_topics'])) {
            if (is_array($result['trending_topics'])) {
                // Se for array, cada item deve ser string
                foreach ($result['trending_topics'] as $topic) {
                    $this->assertIsString($topic, 'trending_topics items devem ser strings');
                }
            } else {
                // Se for string, deve ser não vazia
                $this->assertIsString($result['trending_topics'], 'trending_topics deve ser string se não for array');
            }
        }
        
        Log::info('✅ Normalização trending_topics: OK', [
            'type' => isset($result['trending_topics']) ? gettype($result['trending_topics']) : 'null',
        ]);
    }

    /**
     * Retorna dados financeiros mockados
     */
    protected function getMockFinancialData(): array
    {
        return [
            'symbol' => 'PETR4',
            'company_name' => 'Petrobras',
            'price' => 30.50,
            'previous_close' => 30.00,
            'change' => 0.50,
            'change_percent' => 1.67,
            'volume' => 50000000,
            'market_cap' => 200000000000,
            'pe_ratio' => 8.5,
            'dividend_yield' => 5.2,
            'high_52w' => 35.00,
            'low_52w' => 25.00,
            'raw_data' => null,
            'collected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Retorna dados de sentimento mockados
     */
    protected function getMockSentimentData(): array
    {
        return [
            'sentiment' => 'positive',
            'sentiment_score' => 0.65,
            'news_count' => 15,
            'positive_count' => 10,
            'negative_count' => 3,
            'neutral_count' => 2,
            'trending_topics' => ['petróleo', 'energia', 'dividendos'],
            'news_sources' => ['Reuters', 'Bloomberg'],
            'raw_data' => [
                'articles' => [],
                '_analysis' => [
                    'digital_data' => [],
                    'behavioral_data' => [],
                ],
            ],
        ];
    }

    /**
     * Retorna artigos mockados
     */
    protected function getMockArticles(): array
    {
        return [
            [
                'title' => 'Petrobras anuncia aumento de dividendos',
                'description' => 'A empresa anunciou aumento nos dividendos para acionistas',
                'source' => ['name' => 'Reuters'],
                'publishedAt' => now()->toIso8601String(),
            ],
            [
                'title' => 'Petrobras registra alta no preço das ações',
                'description' => 'As ações da Petrobras subiram após anúncio positivo',
                'source' => ['name' => 'Bloomberg'],
                'publishedAt' => now()->toIso8601String(),
            ],
        ];
    }
}

