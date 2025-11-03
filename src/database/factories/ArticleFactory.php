<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ArticleFactory - Factory para criar artigos/matérias de teste
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbol = $this->faker->randomElement(['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3']);
        $stockSymbol = StockSymbol::where('symbol', $symbol)->first() ?? StockSymbol::factory();

        return [
            'stock_symbol_id' => $stockSymbol->id,
            'symbol' => $symbol,
            'financial_data_id' => FinancialData::factory(),
            'sentiment_analysis_id' => SentimentAnalysis::factory(),
            'title' => "Análise {$symbol}: " . $this->faker->sentence(4),
            'content' => $this->generateArticleContent($symbol),
            'status' => 'pendente_revisao',
            'motivo_reprovacao' => null,
            'recomendacao' => $this->faker->randomElement([
                'Recomenda-se análise técnica e fundamentalista adicional.',
                'Recomenda-se aguardar mais informações antes de investir.',
                'Recomenda-se consultar um analista financeiro antes de tomar decisões.',
            ]),
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'agent_version' => '1.0',
            ],
            'notified_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'published_at' => null,
        ];
    }

    /**
     * Gera conteúdo de artigo baseado no símbolo
     */
    protected function generateArticleContent(string $symbol): string
    {
        $companyName = $this->getCompanyName($symbol);
        
        return "## Análise de {$symbol}\n\n" .
               "### Dados Financeiros\n\n" .
               "A ação {$symbol} ({$companyName}) está sendo negociada a R\$ " . 
               number_format($this->faker->randomFloat(2, 10, 100), 2, ',', '.') . ".\n\n" .
               "### Análise de Sentimento\n\n" .
               "Com base na análise de notícias, o sentimento do mercado é " .
               $this->faker->randomElement(['positivo', 'negativo', 'neutro']) . ".\n\n" .
               "### Recomendação\n\n" .
               "Recomenda-se análise técnica e fundamentalista adicional antes de investir.\n\n" .
               "*Este conteúdo foi gerado automaticamente com auxílio de IA e requer revisão humana antes da publicação.*";
    }

    /**
     * Obtém nome da empresa baseado no símbolo
     */
    protected function getCompanyName(string $symbol): string
    {
        $companies = [
            'PETR4' => 'Petrobras',
            'VALE3' => 'Vale',
            'ITUB4' => 'Itaú Unibanco',
            'BBDC4' => 'Bradesco',
            'ABEV3' => 'Ambev',
        ];

        return $companies[$symbol] ?? 'Empresa';
    }

    /**
     * Indicate that the article is pending review.
     */
    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente_revisao',
            'notified_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }

    /**
     * Indicate that the article is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovado',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the article is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reprovado',
            'motivo_reprovacao' => 'Conteúdo não atende aos critérios de qualidade.',
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'publicado',
            'reviewed_at' => now()->subDays(1),
            'reviewed_by' => User::factory(),
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the article is for a specific symbol.
     */
    public function forSymbol(string $symbol): static
    {
        return $this->state(function (array $attributes) use ($symbol) {
            $stockSymbol = StockSymbol::where('symbol', $symbol)->first() ?? StockSymbol::factory();
            return [
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $symbol,
            ];
        });
    }
}

