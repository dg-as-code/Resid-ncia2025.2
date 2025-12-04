<?php

namespace Tests\Unit\Jobs;

use App\Jobs\FetchFinancialDataJob;
use App\Models\Analysis;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Services\YahooFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class FetchFinancialDataJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_fetches_financial_data_successfully()
    {
        $analysis = Analysis::factory()->create([
            'company_name' => 'Petrobras',
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(YahooFinanceService::class);
        $mockService->shouldReceive('getQuoteByCompanyName')
            ->once()
            ->with('Petrobras')
            ->andReturn([
                'symbol' => 'Petrobras',
                'company_name' => 'Petrobras',
                'price' => 30.50,
                'previous_close' => 30.00,
                'change' => 0.50,
                'change_percent' => 1.67,
                'volume' => 50000000,
                'market_cap' => 200000000000,
                'pe_ratio' => 8.5,
                'dividend_yield' => 5.2,
                'high_52w' => 35.00,
                'low_52w' => 25.00,
                'raw_data' => [],
            ]);

        $this->app->instance(YahooFinanceService::class, $mockService);

        $job = new FetchFinancialDataJob($analysis, 'Petrobras');
        $job->handle();

        $analysis->refresh();
        $this->assertEquals('analyzing_sentiment', $analysis->status);
        $this->assertNotNull($analysis->stock_symbol_id);
        $this->assertNotNull($analysis->financial_data_id);
        $this->assertEquals('Petrobras', $analysis->ticker);

        $this->assertDatabaseHas('financial_data', [
            'symbol' => 'Petrobras',
            'price' => 30.50,
        ]);
    }

    /** @test */
    public function it_handles_service_failure()
    {
        $analysis = Analysis::factory()->create([
            'company_name' => 'InvalidCompany',
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(YahooFinanceService::class);
        $mockService->shouldReceive('getQuoteByCompanyName')
            ->once()
            ->andReturn(null);

        $this->app->instance(YahooFinanceService::class, $mockService);

        $job = new FetchFinancialDataJob($analysis, 'InvalidCompany');

        $this->expectException(\Exception::class);
        $job->handle();

        $analysis->refresh();
        $this->assertEquals('failed', $analysis->status);
        $this->assertNotNull($analysis->error_message);
    }

    /** @test */
    public function it_creates_stock_symbol_if_not_exists()
    {
        $analysis = Analysis::factory()->create([
            'company_name' => 'NewCompany',
            'status' => 'pending',
        ]);

        $mockService = Mockery::mock(YahooFinanceService::class);
        $mockService->shouldReceive('getQuoteByCompanyName')
            ->once()
            ->andReturn([
                'symbol' => 'NEW4',
                'company_name' => 'New Company',
                'price' => 10.00,
                'previous_close' => 9.50,
                'change' => 0.50,
                'change_percent' => 5.26,
                'volume' => 1000000,
                'market_cap' => 1000000000,
                'pe_ratio' => 10.0,
                'dividend_yield' => 2.0,
                'high_52w' => 12.00,
                'low_52w' => 8.00,
                'raw_data' => [],
            ]);

        $this->app->instance(YahooFinanceService::class, $mockService);

        $job = new FetchFinancialDataJob($analysis, 'NewCompany');
        $job->handle();

        $this->assertDatabaseHas('stock_symbols', [
            'symbol' => 'NEW4',
            'company_name' => 'New Company',
        ]);
    }
}

