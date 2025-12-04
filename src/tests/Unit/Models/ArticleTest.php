<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_stock_symbol()
    {
        $stockSymbol = StockSymbol::factory()->create();
        $article = Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);

        $this->assertInstanceOf(StockSymbol::class, $article->stockSymbol);
        $this->assertEquals($stockSymbol->id, $article->stockSymbol->id);
    }

    /** @test */
    public function it_belongs_to_financial_data()
    {
        $stockSymbol = StockSymbol::factory()->create();
        $financialData = FinancialData::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);
        
        $article = Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'financial_data_id' => $financialData->id,
        ]);

        $this->assertInstanceOf(FinancialData::class, $article->financialData);
        $this->assertEquals($financialData->id, $article->financialData->id);
    }

    /** @test */
    public function it_belongs_to_sentiment_analysis()
    {
        $stockSymbol = StockSymbol::factory()->create();
        $sentimentAnalysis = SentimentAnalysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);
        
        $article = Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'sentiment_analysis_id' => $sentimentAnalysis->id,
        ]);

        $this->assertInstanceOf(SentimentAnalysis::class, $article->sentimentAnalysis);
        $this->assertEquals($sentimentAnalysis->id, $article->sentimentAnalysis->id);
    }

    /** @test */
    public function it_belongs_to_reviewer()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'reviewed_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $article->reviewer);
        $this->assertEquals($user->id, $article->reviewer->id);
    }

    /** @test */
    public function it_can_scope_pending_review()
    {
        Article::factory()->create(['status' => 'pendente_revisao']);
        Article::factory()->create(['status' => 'aprovado']);
        Article::factory()->create(['status' => 'publicado']);

        $pending = Article::pendingReview()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pendente_revisao', $pending->first()->status);
    }

    /** @test */
    public function it_can_scope_approved()
    {
        Article::factory()->create(['status' => 'pendente_revisao']);
        Article::factory()->create(['status' => 'aprovado']);
        Article::factory()->create(['status' => 'publicado']);

        $approved = Article::approved()->get();

        $this->assertCount(1, $approved);
        $this->assertEquals('aprovado', $approved->first()->status);
    }

    /** @test */
    public function it_can_scope_published()
    {
        Article::factory()->create(['status' => 'pendente_revisao']);
        Article::factory()->create(['status' => 'aprovado']);
        Article::factory()->create(['status' => 'publicado']);

        $published = Article::published()->get();

        $this->assertCount(1, $published);
        $this->assertEquals('publicado', $published->first()->status);
    }
}

