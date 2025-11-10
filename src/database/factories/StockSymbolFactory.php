<?php

namespace Database\Factories;

use App\Models\StockSymbol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * StockSymbolFactory - Factory para criar ações de teste
 */
class StockSymbolFactory extends Factory
{
    protected $model = StockSymbol::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbols = ['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3', 'WEGE3', 'MGLU3', 'RENT3', 'SUZB3', 'ELET3'];
        $companies = [
            'Petrobras', 'Vale', 'Itaú Unibanco', 'Bradesco', 'Ambev',
            'WEG', 'Magazine Luiza', 'Localiza', 'Suzano', 'Centrais Elétricas Brasileiras'
        ];

        return [
            'symbol' => $this->faker->unique()->randomElement($symbols),
            'company_name' => $this->faker->randomElement($companies),
            'is_active' => $this->faker->boolean(90), // 90% de chance de estar ativo
            'is_default' => $this->faker->boolean(50), // 50% de chance de ser padrão
        ];
    }

    /**
     * Indicate that the symbol is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the symbol is default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the symbol is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

