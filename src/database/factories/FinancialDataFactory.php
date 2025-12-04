<?php

namespace Database\Factories;

use App\Models\FinancialData;
use App\Models\StockSymbol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * FinancialDataFactory - Factory para criar dados financeiros de teste
 */
class FinancialDataFactory extends Factory
{
    protected $model = FinancialData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = $this->faker->randomFloat(4, 10, 100);
        $change = $this->faker->randomFloat(4, -5, 5);
        $changePercent = ($change / $basePrice) * 100;

        return [
            'stock_symbol_id' => StockSymbol::factory(),
            'symbol' => $this->faker->randomElement(['Petrobras', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3']),
            'price' => $basePrice,
            'previous_close' => $basePrice - $change,
            'change' => $change,
            'change_percent' => $changePercent,
            'volume' => $this->faker->numberBetween(1000000, 100000000),
            'market_cap' => $this->faker->numberBetween(1000000000, 500000000000),
            'pe_ratio' => $this->faker->randomFloat(4, 5, 30),
            'dividend_yield' => $this->faker->randomFloat(4, 0, 10),
            'high_52w' => $basePrice * 1.2,
            'low_52w' => $basePrice * 0.8,
            'raw_data' => [
                'source' => 'yahoo_finance',
                'timestamp' => now()->toIso8601String(),
            ],
            'source' => 'yahoo_finance',
            'collected_at' => now(),
        ];
    }

    /**
     * Indicate that the data is for a specific symbol.
     */
    public function forSymbol(string $symbol): static
    {
        return $this->state(function (array $attributes) use ($symbol) {
            $stockSymbol = StockSymbol::where('symbol', $symbol)->first();
            return [
                'stock_symbol_id' => $stockSymbol?->id ?? StockSymbol::factory(),
                'symbol' => $symbol,
            ];
        });
    }
}

