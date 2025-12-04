<?php

namespace Database\Factories;

use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para criar análises de sentimento do mercado
 * 
 * FLUXO DOS AGENTES:
 * Este factory cria análises geradas pelo Agente Pedro, responsável por analisar
 * sentimento de mercado, opiniões da mídia e percepção de marca.
 * 
 * O Agente Pedro:
 * - Busca notícias via NewsAnalysisService
 * - Analisa sentimento com LLM (Gemini)
 * - Gera análise completa incluindo:
 *   - Dados básicos de sentimento
 *   - Análise de mercado e macroeconomia
 *   - Métricas de marca e percepção
 *   - Insights estratégicos
 *   - Dados digitais e comportamentais
 * 
 * Dados gerados são usados pelo Agente Key para criar matérias jornalísticas.
 * 
 * Estrutura de raw_data:
 * - articles: Array de notícias analisadas
 * - _analysis: Análise enriquecida com dados digitais, comportamentais e estratégicos
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
            'symbol' => $this->faker->randomElement(['Petrobras', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3']),
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
                '_analysis' => [
                    'digital_data' => [
                        'volume_mentions' => [
                            'total' => $this->faker->numberBetween(50, 200),
                            'relevance' => $this->faker->randomElement(['alta', 'média', 'baixa']),
                        ],
                        'sentiment_public' => [
                            'positive' => $positiveCount,
                            'negative' => $negativeCount,
                            'neutral' => $neutralCount,
                        ],
                        'engagement' => [
                            'clicks' => $this->faker->numberBetween(100, 1000),
                            'shares' => $this->faker->numberBetween(10, 100),
                            'comments' => $this->faker->numberBetween(5, 50),
                        ],
                        'reach' => [
                            'organic' => $this->faker->numberBetween(1000, 5000),
                            'paid' => $this->faker->numberBetween(0, 1000),
                        ],
                    ],
                    'behavioral_data' => [
                        'purchase_intentions' => [
                            'level' => $this->faker->randomElement(['alto', 'médio', 'baixo']),
                            'trend' => $this->faker->randomElement(['crescendo', 'estável', 'diminuindo']),
                        ],
                        'complaints' => [
                            'count' => $this->faker->numberBetween(0, 20),
                            'main_issues' => $this->faker->words(3),
                        ],
                        'social_feedback' => [
                            'positive' => $this->faker->numberBetween(10, 50),
                            'negative' => $this->faker->numberBetween(0, 20),
                        ],
                        'product_reviews' => [
                            'average_rating' => $this->faker->randomFloat(1, 3.0, 5.0),
                            'total_reviews' => $this->faker->numberBetween(50, 500),
                        ],
                    ],
                    'strategic_insights' => [
                        [
                            'insight' => $this->faker->randomElement([
                                'O público está mais sensível ao preço.',
                                'O concorrente X está ganhando share.',
                                'Há tendência de crescimento em tema Y.',
                                'A satisfação do cliente está caindo.',
                            ]),
                            'category' => $this->faker->randomElement(['preço', 'concorrência', 'tendências', 'satisfação']),
                            'priority' => $this->faker->randomElement(['alta', 'média', 'baixa']),
                        ],
                    ],
                    'cost_optimization' => [
                        'areas_to_cut' => [
                            [
                                'area' => $this->faker->randomElement(['Marketing', 'Operações', 'TI']),
                                'potential_savings' => $this->faker->numberBetween(10000, 100000),
                            ],
                        ],
                        'areas_to_invest' => [
                            [
                                'area' => $this->faker->randomElement(['Inovação', 'Qualidade', 'Atendimento']),
                                'expected_return' => $this->faker->randomElement(['alto', 'médio']),
                            ],
                        ],
                    ],
                ],
            ],
            'source' => 'news_api_llm', // Indica que foi enriquecido com LLM
            'analyzed_at' => now(),
            
            // Campos enriquecidos do Agente Pedro (opcionais, mas podem ser gerados)
            'market_analysis' => $this->faker->optional(0.7)->passthrough([
                'overall_trend' => $this->faker->randomElement(['Tendência positiva', 'Tendência negativa', 'Tendência neutra']),
                'key_drivers' => $this->faker->words(3),
                'risk_factors' => $this->faker->words(2),
                'opportunities' => $this->faker->words(2),
            ]),
            'macroeconomic_analysis' => $this->faker->optional(0.7)->passthrough([
                'economic_context' => $this->faker->sentence(),
                'sector_performance' => $this->faker->sentence(),
                'indicators' => $this->faker->words(3),
            ]),
            'key_insights' => $this->faker->optional(0.7)->passthrough($this->faker->sentences(3)),
            'recommendation' => $this->faker->optional(0.7)->sentence(),
            
            // Métricas de marca (opcionais)
            'total_mentions' => $this->faker->optional(0.6)->numberBetween(50, 500),
            'mentions_peak' => $this->faker->optional(0.6)->passthrough([
                'date' => now()->subDays($this->faker->numberBetween(1, 7))->toDateString(),
                'count' => $this->faker->numberBetween(20, 100),
            ]),
            'sentiment_breakdown' => $this->faker->optional(0.6)->passthrough([
                'positive' => $positiveCount,
                'negative' => $negativeCount,
                'neutral' => $neutralCount,
            ]),
            'engagement_metrics' => $this->faker->optional(0.6)->passthrough([
                'clicks' => $this->faker->numberBetween(100, 1000),
                'shares' => $this->faker->numberBetween(10, 100),
                'comments' => $this->faker->numberBetween(5, 50),
            ]),
            'engagement_score' => $this->faker->optional(0.6)->randomFloat(2, 0, 10),
            'brand_perception' => $this->faker->optional(0.6)->passthrough([
                'overall' => $this->faker->randomElement(['positiva', 'neutra', 'negativa']),
                'trust_score' => $this->faker->randomFloat(2, 0, 10),
                'quality_score' => $this->faker->randomFloat(2, 0, 10),
            ]),
            'actionable_insights' => $this->faker->optional(0.6)->passthrough([
                [
                    'insight' => $this->faker->sentence(),
                    'category' => $this->faker->randomElement(['preço', 'concorrência', 'tendências']),
                    'priority' => $this->faker->randomElement(['alta', 'média', 'baixa']),
                ],
            ]),
            'risk_alerts' => $this->faker->optional(0.5)->passthrough([
                [
                    'alert' => $this->faker->sentence(),
                    'severity' => $this->faker->randomElement(['crítica', 'alta', 'média']),
                    'category' => $this->faker->randomElement(['financeiro', 'operacional', 'reputacional']),
                ],
            ]),
        ];
    }

    /**
     * Estado: positive - Sentimento positivo do mercado
     * 
     * Análise indica sentimento positivo, com mais notícias positivas que negativas.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'positive',
            'sentiment_score' => $this->faker->randomFloat(4, 0.1, 1.0),
            'positive_count' => $this->faker->numberBetween(15, 25),
            'negative_count' => $this->faker->numberBetween(0, 5),
            'neutral_count' => $this->faker->numberBetween(0, 5),
        ]);
    }

    /**
     * Estado: negative - Sentimento negativo do mercado
     * 
     * Análise indica sentimento negativo, com mais notícias negativas que positivas.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'negative',
            'sentiment_score' => $this->faker->randomFloat(4, -1.0, -0.1),
            'positive_count' => $this->faker->numberBetween(0, 5),
            'negative_count' => $this->faker->numberBetween(15, 25),
            'neutral_count' => $this->faker->numberBetween(0, 5),
        ]);
    }

    /**
     * Estado: neutral - Sentimento neutro do mercado
     * 
     * Análise indica sentimento neutro, com distribuição equilibrada de notícias.
     */
    public function neutral(): static
    {
        return $this->state(fn (array $attributes) => [
            'sentiment' => 'neutral',
            'sentiment_score' => $this->faker->randomFloat(4, -0.1, 0.1),
            'positive_count' => $this->faker->numberBetween(5, 10),
            'negative_count' => $this->faker->numberBetween(5, 10),
            'neutral_count' => $this->faker->numberBetween(5, 10),
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

