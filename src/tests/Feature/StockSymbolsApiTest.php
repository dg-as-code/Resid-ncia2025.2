<?php

namespace Tests\Feature;

use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockSymbolsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_stock_symbols()
    {
        StockSymbol::factory()->count(5)->create();

        $response = $this->getJson('/api/stock-symbols');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'symbol',
                        'company_name',
                        'is_active',
                        'is_default',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_show_specific_stock_symbol()
    {
        $symbol = StockSymbol::factory()->create([
            'symbol' => 'PETR4',
            'company_name' => 'Petrobras',
        ]);

        // Não usar eager loading para evitar problemas com relacionamentos em testes
        $response = $this->getJson("/api/stock-symbols/{$symbol->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $symbol->id,
                    'symbol' => 'PETR4',
                    'company_name' => 'Petrobras',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_create_symbol()
    {
        $response = $this->postJson('/api/stock-symbols', [
            'symbol' => 'NEW4',
            'company_name' => 'New Company',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_create_symbol_with_authentication()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/stock-symbols', [
                'symbol' => 'NEW4',
                'company_name' => 'New Company',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'symbol' => 'NEW4',
                    'company_name' => 'New Company',
                ],
            ]);

        $this->assertDatabaseHas('stock_symbols', [
            'symbol' => 'NEW4',
            'company_name' => 'New Company',
        ]);
    }
}

