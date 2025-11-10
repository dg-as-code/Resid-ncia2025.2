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
    public function it_can_search_news_for_a_symbol()
    {
        Http::fake([
            'newsapi.org/*' => Http::response([
                'status' => 'ok',
                'totalResults' => 10,
                'articles' => [
                    [
                        'title' => 'Test News',
                        'description' => 'Test Description',
                        'url' => 'https://example.com/news',
                        'publishedAt' => now()->toIso8601String(),
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->searchNews('PETR4', 'Petrobras', 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('articles', $result);
    }

    /** @test */
    public function it_returns_mock_data_when_api_key_not_configured()
    {
        config(['services.news_api.api_key' => null]);

        $result = $this->service->searchNews('PETR4', 'Petrobras', 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('articles', $result);
    }

    /** @test */
    public function it_can_analyze_sentiment()
    {
        $news = [
            'articles' => [
                ['title' => 'Positive news', 'description' => 'Great results'],
                ['title' => 'Negative news', 'description' => 'Bad performance'],
            ],
        ];

        $result = $this->service->analyzeSentiment($news);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('sentiment_score', $result);
        $this->assertContains($result['sentiment'], ['positive', 'negative', 'neutral']);
    }
}

