<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DraftArticleJob;
use App\Models\Analysis;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use App\Services\LLMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DraftArticleJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_drafts_article_successfully()
    {
        $stockSymbol = StockSymbol::factory()->create([
            'symbol' => 'Petrobras',
        ]);

        $financialData = FinancialData::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'price' => 30.50,
            'change_percent' => 1.67,
        ]);

        $sentimentAnalysis = SentimentAnalysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'sentiment' => 'positive',
            'sentiment_score' => 0.75,
        ]);

        $analysis = Analysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'financial_data_id' => $financialData->id,
            'sentiment_analysis_id' => $sentimentAnalysis->id,
            'status' => 'drafting_article',
        ]);

        $mockService = Mockery::mock(LLMService::class);
        $mockService->shouldReceive('generateArticle')
            ->once()
            ->andReturn([
                'title' => 'Petrobras mostra crescimento no mercado',
                'content' => 'A Petrobras apresentou...',
            ]);

        $this->app->instance(LLMService::class, $mockService);

        $job = new DraftArticleJob($analysis);
        $job->handle();

        $analysis->refresh();
        $this->assertEquals('pending_review', $analysis->status);
        $this->assertNotNull($analysis->article_id);

        $this->assertDatabaseHas('articles', [
            'stock_symbol_id' => $stockSymbol->id,
            'title' => 'Petrobras mostra crescimento no mercado',
            'status' => 'pendente_revisao',
        ]);
    }

    /** @test */
    public function it_handles_missing_data()
    {
        $analysis = Analysis::factory()->create([
            'financial_data_id' => null,
            'sentiment_analysis_id' => null,
            'status' => 'drafting_article',
        ]);

        $job = new DraftArticleJob($analysis);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dados insuficientes');

        $job->handle();

        $analysis->refresh();
        $this->assertEquals('failed', $analysis->status);
    }
}

