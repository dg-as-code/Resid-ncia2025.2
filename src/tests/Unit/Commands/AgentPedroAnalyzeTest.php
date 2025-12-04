<?php

namespace Tests\Unit\Commands;

use App\Models\StockSymbol;
use App\Models\SentimentAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AgentPedroAnalyzeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_analyze_sentiment_for_specific_symbol()
    {
        StockSymbol::factory()->create([
            'symbol' => 'Petrobras',
            'is_active' => true,
        ]);

        $this->artisan('agent:pedro:analyze', ['--symbol' => 'Petrobras'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('sentiment_analysis', [
            'symbol' => 'Petrobras',
        ]);
    }

    /** @test */
    public function it_can_analyze_sentiment_for_all_active_symbols()
    {
        StockSymbol::factory()->count(3)->create([
            'is_active' => true,
        ]);

        $this->artisan('agent:pedro:analyze', ['--all' => true])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_invalid_symbol_gracefully()
    {
        StockSymbol::factory()->create([
            'symbol' => 'INVALID',
            'is_active' => true,
        ]);

        $this->artisan('agent:pedro:analyze', ['--symbol' => 'INVALID'])
            ->assertExitCode(0);
    }
}

