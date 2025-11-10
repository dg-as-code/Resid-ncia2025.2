<?php

namespace Tests\Unit\Commands;

use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentKeyComposeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_generate_article_from_financial_and_sentiment_data()
    {
        $symbol = StockSymbol::factory()->create([
            'symbol' => 'PETR4',
            'is_active' => true,
        ]);

        $financialData = FinancialData::factory()->create([
            'stock_symbol_id' => $symbol->id,
            'symbol' => 'PETR4',
            'collected_at' => now(),
        ]);

        $sentiment = SentimentAnalysis::factory()->create([
            'stock_symbol_id' => $symbol->id,
            'symbol' => 'PETR4',
            'analyzed_at' => now(),
        ]);

        $this->artisan('agent:key:compose')
            ->expectsOutput('✅ Agente Key executado com sucesso')
            ->assertExitCode(0);

        $this->assertDatabaseHas('articles', [
            'symbol' => 'PETR4',
            'status' => 'pendente_revisao',
        ]);
    }

    /** @test */
    public function it_skips_symbols_without_recent_data()
    {
        $symbol = StockSymbol::factory()->create([
            'symbol' => 'PETR4',
            'is_active' => true,
        ]);

        // Criar dados antigos (mais de 24 horas)
        FinancialData::factory()->create([
            'stock_symbol_id' => $symbol->id,
            'symbol' => 'PETR4',
            'collected_at' => now()->subDays(2),
        ]);

        $this->artisan('agent:key:compose')
            ->expectsOutput('✅ Agente Key executado com sucesso')
            ->assertExitCode(0);

        // Não deve criar artigo para dados antigos
        $this->assertDatabaseMissing('articles', [
            'symbol' => 'PETR4',
        ]);
    }
}

