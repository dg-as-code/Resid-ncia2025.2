<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AnalyzeMarketSentimentJob;
use App\Models\Analysis;
use App\Models\StockSymbol;
use App\Models\SentimentAnalysis;
use App\Services\NewsAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalyzeMarketSentimentJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_analyzes_market_sentiment_successfully()
    {
        $stockSymbol = StockSymbol::factory()->create([
            'symbol' => 'Petrobras',
            'company_name' => 'Petrobras',
        ]);

        $analysis = Analysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'status' => 'analyzing_sentiment',
        ]);

        $mockService = Mockery::mock(NewsAnalysisService::class);
        $mockService->shouldReceive('searchNews')
            ->once()
            ->andReturn([
                ['title' => 'Notícia 1', 'description' => 'Descrição 1'],
                ['title' => 'Notícia 2', 'description' => 'Descrição 2'],
            ]);

        $mockService->shouldReceive('analyzeSentiment')
            ->once()
            ->andReturn([
                'sentiment' => 'positive',
                'sentiment_score' => 0.75,
                'news_count' => 2,
                'positive_count' => 2,
                'negative_count' => 0,
                'neutral_count' => 0,
                'trending_topics' => 'Crescimento, Lucros',
                'news_sources' => ['Fonte 1', 'Fonte 2'],
                'raw_data' => [],
            ]);

        $this->app->instance(NewsAnalysisService::class, $mockService);

        $job = new AnalyzeMarketSentimentJob($analysis);
        $job->handle();

        $analysis->refresh();
        $this->assertEquals('drafting_article', $analysis->status);
        $this->assertNotNull($analysis->sentiment_analysis_id);

        $this->assertDatabaseHas('sentiment_analysis', [
            'stock_symbol_id' => $stockSymbol->id,
            'sentiment' => 'positive',
            'sentiment_score' => 0.75,
        ]);
    }

    /** @test */
    public function it_handles_missing_stock_symbol()
    {
        $analysis = Analysis::factory()->create([
            'stock_symbol_id' => null,
            'status' => 'analyzing_sentiment',
        ]);

        $job = new AnalyzeMarketSentimentJob($analysis);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('StockSymbol não encontrado');

        $job->handle();

        $analysis->refresh();
        $this->assertEquals('failed', $analysis->status);
    }
}

