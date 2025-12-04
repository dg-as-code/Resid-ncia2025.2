<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\User;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para criar instâncias de Analysis (Análises Financeiras)
 * 
 * FLUXO DOS AGENTES:
 * Este factory facilita a criação de análises em diferentes estágios do fluxo:
 * 
 * 1. pending → Análise criada, aguardando início
 * 2. fetching_financial_data → Agente Júlia coletando dados financeiros
 * 3. analyzing_sentiment → Agente Pedro analisando sentimento
 * 4. drafting_article → Agente Key gerando matéria jornalística
 * 5. pending_review → Matéria pronta, aguardando revisão humana
 * 6. completed → Análise concluída (aprovada e publicada)
 * 7. failed → Análise falhou em alguma etapa
 * 
 * Métodos auxiliares:
 * - withStockSymbol(): Adiciona StockSymbol
 * - withFinancialData(): Adiciona FinancialData (Agente Júlia concluído)
 * - withSentimentAnalysis(): Adiciona SentimentAnalysis (Agente Pedro concluído)
 * - withArticle(): Adiciona Article (Agente Key concluído)
 * - complete(): Cria análise completa com todos os relacionamentos
 */
class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    /**
     * Define o estado padrão do model.
     * 
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'ticker' => $this->faker->regexify('[A-Z]{4}[0-9]'),
            'status' => $this->faker->randomElement([
                'pending',
                'fetching_financial_data',
                'analyzing_sentiment',
                'drafting_article',
                'pending_review',
                'completed',
                'failed',
            ]),
            'user_id' => User::factory(),
            'stock_symbol_id' => null,
            'financial_data_id' => null,
            'sentiment_analysis_id' => null,
            'article_id' => null,
            'error_message' => null,
            'metadata' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Estado: pending - Análise criada, aguardando início do processamento
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Estado: fetching_financial_data - Agente Júlia coletando dados financeiros
     */
    public function fetchingFinancialData(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'fetching_financial_data',
            'started_at' => now()->subMinutes(2),
            'completed_at' => null,
        ]);
    }

    /**
     * Estado: analyzing_sentiment - Agente Pedro analisando sentimento
     */
    public function analyzingSentiment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'analyzing_sentiment',
            'started_at' => now()->subMinutes(5),
            'completed_at' => null,
        ]);
    }

    /**
     * Estado: drafting_article - Agente Key gerando matéria jornalística
     */
    public function draftingArticle(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'drafting_article',
            'started_at' => now()->subMinutes(8),
            'completed_at' => null,
        ]);
    }

    /**
     * Estado: pending_review - Matéria pronta, aguardando revisão humana
     */
    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_review',
            'started_at' => now()->subMinutes(10),
            'completed_at' => null,
        ]);
    }

    /**
     * Estado: completed - Análise concluída (aprovada e publicada)
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHours(1),
            'completed_at' => now(),
        ]);
    }

    /**
     * Estado: failed - Análise falhou em alguma etapa
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Erro de teste simulado',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    /**
     * Adiciona StockSymbol à análise
     * 
     * Cria ou usa StockSymbol existente e atualiza ticker.
     * 
     * @return static
     */
    public function withStockSymbol(): static
    {
        return $this->state(function (array $attributes) {
            $stockSymbol = $attributes['stock_symbol_id'] 
                ? StockSymbol::find($attributes['stock_symbol_id'])
                : StockSymbol::factory()->create();
            
            return [
                'stock_symbol_id' => $stockSymbol->id,
                'ticker' => $stockSymbol->symbol,
                'company_name' => $stockSymbol->company_name ?? $attributes['company_name'] ?? $this->faker->company(),
            ];
        });
    }

    /**
     * Adiciona FinancialData à análise (Agente Júlia concluído)
     * 
     * Cria FinancialData associado ao StockSymbol e atualiza status para 'analyzing_sentiment'.
     * 
     * @return static
     */
    public function withFinancialData(): static
    {
        return $this->state(function (array $attributes) {
            $stockSymbol = $attributes['stock_symbol_id'] 
                ? StockSymbol::find($attributes['stock_symbol_id'])
                : StockSymbol::factory()->create();
            
            $financialData = FinancialData::factory()->create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
            ]);

            return [
                'stock_symbol_id' => $stockSymbol->id,
                'financial_data_id' => $financialData->id,
                'ticker' => $stockSymbol->symbol,
                'company_name' => $stockSymbol->company_name ?? $attributes['company_name'] ?? $this->faker->company(),
                'status' => $attributes['status'] ?? 'analyzing_sentiment',
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'financial_data_collected_at' => $financialData->collected_at?->toIso8601String(),
                ]),
            ];
        });
    }

    /**
     * Adiciona SentimentAnalysis à análise (Agente Pedro concluído)
     * 
     * Cria SentimentAnalysis associado ao StockSymbol e atualiza status para 'drafting_article'.
     * Requer FinancialData (Agente Júlia) para contexto.
     * 
     * @return static
     */
    public function withSentimentAnalysis(): static
    {
        return $this->state(function (array $attributes) {
            $stockSymbol = $attributes['stock_symbol_id'] 
                ? StockSymbol::find($attributes['stock_symbol_id'])
                : StockSymbol::factory()->create();
            
            // Garante que FinancialData existe (Agente Júlia deve ter concluído)
            if (!$attributes['financial_data_id']) {
                $financialData = FinancialData::factory()->create([
                    'stock_symbol_id' => $stockSymbol->id,
                    'symbol' => $stockSymbol->symbol,
                ]);
                $attributes['financial_data_id'] = $financialData->id;
            }
            
            $sentimentAnalysis = SentimentAnalysis::factory()->create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
            ]);

            return [
                'stock_symbol_id' => $stockSymbol->id,
                'sentiment_analysis_id' => $sentimentAnalysis->id,
                'status' => $attributes['status'] ?? 'drafting_article',
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'sentiment_analyzed_at' => $sentimentAnalysis->analyzed_at?->toIso8601String(),
                ]),
            ];
        });
    }

    /**
     * Adiciona Article à análise (Agente Key concluído)
     * 
     * Cria Article associado ao StockSymbol e atualiza status para 'pending_review'.
     * Requer FinancialData e SentimentAnalysis (Agentes Júlia e Pedro).
     * 
     * @return static
     */
    public function withArticle(): static
    {
        return $this->state(function (array $attributes) {
            $stockSymbol = $attributes['stock_symbol_id'] 
                ? StockSymbol::find($attributes['stock_symbol_id'])
                : StockSymbol::factory()->create();
            
            // Garante que FinancialData existe (Agente Júlia)
            if (!$attributes['financial_data_id']) {
                $financialData = FinancialData::factory()->create([
                    'stock_symbol_id' => $stockSymbol->id,
                    'symbol' => $stockSymbol->symbol,
                ]);
                $attributes['financial_data_id'] = $financialData->id;
            }
            
            // Garante que SentimentAnalysis existe (Agente Pedro)
            if (!$attributes['sentiment_analysis_id']) {
                $sentimentAnalysis = SentimentAnalysis::factory()->create([
                    'stock_symbol_id' => $stockSymbol->id,
                    'symbol' => $stockSymbol->symbol,
                ]);
                $attributes['sentiment_analysis_id'] = $sentimentAnalysis->id;
            }
            
            $article = Article::factory()->create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
                'financial_data_id' => $attributes['financial_data_id'],
                'sentiment_analysis_id' => $attributes['sentiment_analysis_id'],
                'status' => 'pendente_revisao',
            ]);

            return [
                'stock_symbol_id' => $stockSymbol->id,
                'article_id' => $article->id,
                'status' => $attributes['status'] ?? 'pending_review',
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'article_generated_at' => $article->created_at->toIso8601String(),
                ]),
            ];
        });
    }

    /**
     * Cria análise completa com todos os relacionamentos (fluxo completo)
     * 
     * Simula o fluxo completo dos agentes:
     * - Agente Júlia: FinancialData criado
     * - Agente Pedro: SentimentAnalysis criado
     * - Agente Key: Article criado
     * - Status: pending_review (aguardando revisão humana)
     * 
     * Uso:
     * Analysis::factory()->complete()->create();
     * 
     * @return static
     */
    public function complete(): static
    {
        return $this->state(function (array $attributes) {
            $stockSymbol = StockSymbol::factory()->create();
            
            $financialData = FinancialData::factory()->create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
            ]);
            
            $sentimentAnalysis = SentimentAnalysis::factory()->create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
            ]);
            
            $article = Article::factory()->create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $stockSymbol->symbol,
                'financial_data_id' => $financialData->id,
                'sentiment_analysis_id' => $sentimentAnalysis->id,
                'status' => 'pendente_revisao',
            ]);

            return [
                'stock_symbol_id' => $stockSymbol->id,
                'financial_data_id' => $financialData->id,
                'sentiment_analysis_id' => $sentimentAnalysis->id,
                'article_id' => $article->id,
                'ticker' => $stockSymbol->symbol,
                'company_name' => $stockSymbol->company_name,
                'status' => 'pending_review',
                'started_at' => now()->subMinutes(15),
                'metadata' => [
                    'financial_data_collected_at' => $financialData->collected_at?->toIso8601String(),
                    'sentiment_analyzed_at' => $sentimentAnalysis->analyzed_at?->toIso8601String(),
                    'article_generated_at' => $article->created_at->toIso8601String(),
                    'flow' => 'complete',
                ],
            ];
        });
    }
}

