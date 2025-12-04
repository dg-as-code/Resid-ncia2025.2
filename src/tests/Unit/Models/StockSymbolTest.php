<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockSymbolTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_many_financial_data()
    {
        $stockSymbol = StockSymbol::factory()->create();
        FinancialData::factory()->count(3)->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);

        $this->assertCount(3, $stockSymbol->financialData);
    }

    /** @test */
    public function it_has_many_sentiment_analyses()
    {
        $stockSymbol = StockSymbol::factory()->create();
        SentimentAnalysis::factory()->count(2)->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);

        $this->assertCount(2, $stockSymbol->sentimentAnalyses);
    }

    /** @test */
    public function it_has_many_articles()
    {
        $stockSymbol = StockSymbol::factory()->create();
        Article::factory()->count(5)->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);

        $this->assertCount(5, $stockSymbol->articles);
    }

    /** @test */
    public function it_can_scope_active()
    {
        StockSymbol::factory()->create(['is_active' => true]);
        StockSymbol::factory()->create(['is_active' => false]);
        StockSymbol::factory()->create(['is_active' => true]);

        $active = StockSymbol::active()->get();

        $this->assertCount(2, $active);
        $this->assertTrue($active->first()->is_active);
    }

    /** @test */
    public function it_can_scope_default()
    {
        StockSymbol::factory()->create(['is_default' => true]);
        StockSymbol::factory()->create(['is_default' => false]);
        StockSymbol::factory()->create(['is_default' => true]);

        $default = StockSymbol::default()->get();

        $this->assertCount(2, $default);
        $this->assertTrue($default->first()->is_default);
    }

    /** @test */
    public function it_has_latest_financial_data()
    {
        $stockSymbol = StockSymbol::factory()->create();
        
        $old = FinancialData::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'symbol' => $stockSymbol->symbol,
            'collected_at' => now()->subDays(2),
        ]);
        
        $latest = FinancialData::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'symbol' => $stockSymbol->symbol,
            'collected_at' => now(),
        ]);

        $latestData = $stockSymbol->latestFinancialData;
        $this->assertNotNull($latestData);
        $this->assertEquals($latest->id, $latestData->id);
    }

    /** @test */
    public function it_has_latest_sentiment_analysis()
    {
        $stockSymbol = StockSymbol::factory()->create();
        
        $old = SentimentAnalysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'symbol' => $stockSymbol->symbol,
            'analyzed_at' => now()->subDays(2),
        ]);
        
        $latest = SentimentAnalysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'symbol' => $stockSymbol->symbol,
            'analyzed_at' => now(),
        ]);

        $latestAnalysis = $stockSymbol->latestSentimentAnalysis;
        $this->assertNotNull($latestAnalysis);
        $this->assertEquals($latest->id, $latestAnalysis->id);
    }
}

