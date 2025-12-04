<?php

namespace Tests\Unit\Models;

use App\Models\Analysis;
use App\Models\User;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_user()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $analysis->user);
        $this->assertEquals($user->id, $analysis->user->id);
    }

    /** @test */
    public function it_belongs_to_stock_symbol()
    {
        $stockSymbol = StockSymbol::factory()->create();
        $analysis = Analysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);

        $this->assertInstanceOf(StockSymbol::class, $analysis->stockSymbol);
        $this->assertEquals($stockSymbol->id, $analysis->stockSymbol->id);
    }

    /** @test */
    public function it_belongs_to_financial_data()
    {
        $financialData = FinancialData::factory()->create();
        $analysis = Analysis::factory()->create([
            'financial_data_id' => $financialData->id,
        ]);

        $this->assertInstanceOf(FinancialData::class, $analysis->financialData);
        $this->assertEquals($financialData->id, $analysis->financialData->id);
    }

    /** @test */
    public function it_belongs_to_sentiment_analysis()
    {
        $sentimentAnalysis = SentimentAnalysis::factory()->create();
        $analysis = Analysis::factory()->create([
            'sentiment_analysis_id' => $sentimentAnalysis->id,
        ]);

        $this->assertInstanceOf(SentimentAnalysis::class, $analysis->sentimentAnalysis);
        $this->assertEquals($sentimentAnalysis->id, $analysis->sentimentAnalysis->id);
    }

    /** @test */
    public function it_belongs_to_article()
    {
        $article = Article::factory()->create();
        $analysis = Analysis::factory()->create([
            'article_id' => $article->id,
        ]);

        $this->assertInstanceOf(Article::class, $analysis->article);
        $this->assertEquals($article->id, $analysis->article->id);
    }

    /** @test */
    public function it_can_scope_pending()
    {
        Analysis::factory()->create(['status' => 'pending']);
        Analysis::factory()->create(['status' => 'completed']);

        $pending = Analysis::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    /** @test */
    public function it_can_scope_processing()
    {
        Analysis::factory()->create(['status' => 'fetching_financial_data']);
        Analysis::factory()->create(['status' => 'analyzing_sentiment']);
        Analysis::factory()->create(['status' => 'drafting_article']);
        Analysis::factory()->create(['status' => 'completed']);

        $processing = Analysis::processing()->get();

        $this->assertCount(3, $processing);
    }

    /** @test */
    public function it_can_scope_pending_review()
    {
        Analysis::factory()->create(['status' => 'pending_review']);
        Analysis::factory()->create(['status' => 'completed']);

        $pendingReview = Analysis::pendingReview()->get();

        $this->assertCount(1, $pendingReview);
        $this->assertEquals('pending_review', $pendingReview->first()->status);
    }

    /** @test */
    public function it_can_scope_completed()
    {
        Analysis::factory()->create(['status' => 'completed']);
        Analysis::factory()->create(['status' => 'pending']);

        $completed = Analysis::completed()->get();

        $this->assertCount(1, $completed);
        $this->assertEquals('completed', $completed->first()->status);
    }

    /** @test */
    public function it_can_scope_failed()
    {
        Analysis::factory()->create(['status' => 'failed']);
        Analysis::factory()->create(['status' => 'completed']);

        $failed = Analysis::failed()->get();

        $this->assertCount(1, $failed);
        $this->assertEquals('failed', $failed->first()->status);
    }
}

