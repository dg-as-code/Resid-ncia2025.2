<?php

namespace Database\Factories;

use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * SentimentAnalysisFactory - Factory para criar análises de sentimento de teste
 */
class SentimentAnalysisFactory extends Factory
{
    protected $model = SentimentAnalysis::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sentiment = $this->faker->randomElement(['positive', 'negative', 'neutral']);
        $newsCount = $this->faker->numberBetween(5, 30);
        
        // Gera contagens baseadas no sentimento
        $positiveCount = $sentiment === 'positive' ? $this->faker->numberBetween(10, 20) : $this->faker->numberBetween(0, 10);
        $negativeCount = $sentiment === 'negative' ? $this->faker->numberBetween(10, 20) : $this->faker->numberBetween(0, 10);
        $neutralCount = $newsCount - $positiveCount - $negativeCount;
        
        $sentimentScore = $sentiment === 'positive' 
            ? $this->faker->randomFloat(4, 0.1, 1.0)
            : ($sentiment === 'negative' 
                ? $this->faker->randomFloat(4, -1.0, -0.1)
                : $this->faker->randomFloat(4, -0.1, 0.1));

        return [
            'stock_symbol_id' => StockSymbol::factory(),
            'symbol' => $this->faker->randomElement(['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3']),
            'sentiment' => $sentiment,
            'sentiment_score' => $sentimentScore,
            'news_count' => $newsCount,
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
            'neutral_count' => $neutralCount,
            'trending_topics' => implode(', ', $this->faker->words(5)),
            'news_sources' => [
                $this->faker->randomElement(['G1', 'Folha', 'Estadão', 'Valor', 'Infomoney']),
                $this->faker->randomElement(['Reuters', 'Bloomberg', 'Financial News']),
            ],
            'raw_data' => [
                'articles' => [],
                'analyzed_at' => now()->toIso8601String(),
            ],
            'source' => 'news_api',
            'analyzed_at' => now(),
        ];
    }

    /**
     * Indicate that the sentiment is positive.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'positive',
            'sentiment_score' => $this->faker->randomFloat(4, 0.1, 1.0),
            'positive_count' => $this->faker->numberBetween(15, 25),
        ]);
    }

    /**
     * Indicate that the sentiment is negative.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'negative',
            'sentiment_score' => $this->faker->randomFloat(4, -1.0, -0.1),
            'negative_count' => $this->faker->numberBetween(15, 25),
        ]);
    }

    /**
     * Indicate that the sentiment is neutral.
     */
    public function neutral(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'neutral',
            'sentiment_score' => $this->faker->randomFloat(4, -0.1, 0.1),
        ]);
    }

    /**
     * Indicate that the analysis is for a specific symbol.
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

