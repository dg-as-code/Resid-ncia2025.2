<?php

namespace Tests\Unit\Services;

use App\Services\NewsAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NewsAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NewsAnalysisService();
    }

    /** @test */
    public function it_can_search_news()
    {
        Http::fake([
            'newsapi.org/*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Notícia 1',
                        'description' => 'Descrição 1',
                        'url' => 'https://example.com/1',
                        'publishedAt' => now()->toIso8601String(),
                    ],
                    [
                        'title' => 'Notícia 2',
                        'description' => 'Descrição 2',
                        'url' => 'https://example.com/2',
                        'publishedAt' => now()->toIso8601String(),
                    ],
                ],
            ], 200),
        ]);

        config(['services.news_api.key' => 'test-key']);

        $articles = $this->service->searchNews('Petrobras', 'Petrobras', 10);

        $this->assertIsArray($articles);
        $this->assertGreaterThan(0, count($articles));
    }

    /** @test */
    public function it_returns_mock_news_when_api_unavailable()
    {
        config(['services.news_api.key' => null]);

        $articles = $this->service->searchNews('Petrobras', 'Petrobras', 10);

        $this->assertIsArray($articles);
        $this->assertGreaterThan(0, count($articles));
    }

    /** @test */
    public function it_can_analyze_sentiment()
    {
        $articles = [
            [
                'title' => 'Boa notícia sobre a empresa',
                'description' => 'Crescimento positivo',
            ],
            [
                'title' => 'Empresa em alta',
                'description' => 'Resultados excelentes',
            ],
        ];

        $analysis = $this->service->analyzeSentiment($articles);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('sentiment', $analysis);
        $this->assertArrayHasKey('sentiment_score', $analysis);
        $this->assertArrayHasKey('news_count', $analysis);
        $this->assertEquals(2, $analysis['news_count']);
    }
}
