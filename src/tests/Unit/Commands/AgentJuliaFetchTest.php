<?php

namespace Tests\Unit\Commands;

use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Console\Commands\AgentJuliaFetch;
use App\Services\YahooFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AgentJuliaFetchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_data_for_specific_symbol()
    {
        StockSymbol::factory()->create([
            'symbol' => 'PETR4',
            'is_active' => true,
        ]);

        $this->artisan('agent:julia:fetch', ['--symbol' => 'PETR4'])
            ->expectsOutput('✅ Agente Júlia executado com sucesso')
            ->assertExitCode(0);

        $this->assertDatabaseHas('financial_data', [
            'symbol' => 'PETR4',
        ]);
    }

    /** @test */
    public function it_can_fetch_data_for_all_default_symbols()
    {
        StockSymbol::factory()->count(3)->create([
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->artisan('agent:julia:fetch', ['--all' => true])
            ->expectsOutput('✅ Agente Júlia executado com sucesso')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_logs_errors_when_api_fails()
    {
        Log::shouldReceive('channel')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once();

        StockSymbol::factory()->create([
            'symbol' => 'INVALID',
            'is_active' => true,
        ]);

        $this->artisan('agent:julia:fetch', ['--symbol' => 'INVALID'])
            ->assertExitCode(1);
    }
}

