<?php

namespace Tests\Unit\Services;

use App\Services\YahooFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YahooFinanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected YahooFinanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YahooFinanceService();
    }

    /** @test */
    public function it_can_get_quote_for_a_symbol()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
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
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        config(['services.llm.openai.api_key' => 'test-key']);

        $result = $this->service->getQuote('PETR4');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('price', $result);
        $this->assertEquals(30.50, $result['price']);
    }

    /** @test */
    public function it_returns_mock_data_when_api_key_not_configured()
    {
        config(['services.llm.openai.api_key' => null]);

        $result = $this->service->getQuote('PETR4');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('symbol', $result);
    }

    /** @test */
    public function it_handles_openai_api_error_gracefully()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([], 500),
        ]);

        config(['services.llm.openai.api_key' => 'test-key']);

        $result = $this->service->getQuote('PETR4');

        // Deve retornar mock data em caso de erro
        $this->assertNotNull($result);
        $this->assertArrayHasKey('price', $result);
    }
}

