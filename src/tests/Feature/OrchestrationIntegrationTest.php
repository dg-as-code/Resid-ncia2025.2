<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Testes de Integração Completa do Fluxo de Orquestração
 * 
 * Testa o fluxo end-to-end: entrada → processamento → revisão → publicação
 */
class OrchestrationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar API keys para testes (já configurado no TestCase base)
        // Apenas garantir que está configurado
        config([
            'services.llm.gemini.api_key' => 'test-api-key',
            'services.llm.provider' => 'gemini',
        ]);
    }

    /** @test */
    public function it_can_execute_complete_orchestration_flow_with_human_review()
    {
        // Mock Gemini API para Júlia (dados financeiros)
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                // Resposta 1: Dados financeiros (Júlia)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'symbol' => 'PETR4',
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
                                        ])
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200)
                // Resposta 2: Artigo (Key)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => '<h1>Análise Petrobras: Mercado em Alta</h1><p>A Petrobras registrou alta de 1.67% no pregão de hoje...</p>'
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200),
            'newsapi.org/*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Petrobras anuncia resultados positivos',
                        'description' => 'Petrobras reporta lucro recorde no trimestre',
                        'publishedAt' => now()->toIso8601String(),
                        'url' => 'https://example.com/news1',
                    ],
                    [
                        'title' => 'Análise: Petrobras em alta',
                        'description' => 'Especialistas veem potencial de crescimento',
                        'publishedAt' => now()->subHours(2)->toIso8601String(),
                        'url' => 'https://example.com/news2',
                    ],
                ]
            ], 200),
        ]);

        // PASSO 1: Executar orquestração
        $response = $this->postJson('/api/orchestrate', [
            'company_name' => 'Petrobras',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'pending_review',
            ]);

        $articleId = $response->json('article_id');
        $this->assertNotNull($articleId);

        // Verificar que artigo foi criado
        $this->assertDatabaseHas('articles', [
            'id' => $articleId,
            'status' => 'pendente_revisao',
            'symbol' => 'PETR4',
        ]);

        // Verificar que dados financeiros foram salvos
        $this->assertDatabaseHas('financial_data', [
            'symbol' => 'PETR4',
        ]);

        // Verificar que análise de sentimento foi salva
        $this->assertDatabaseHas('sentiment_analysis', [
            'symbol' => 'PETR4',
        ]);

        // PASSO 2: Revisão humana - Aprovar
        $user = User::factory()->create();
        $token = $this->generateJwtToken($user);
        $reviewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/orchestrate/{$articleId}/review", [
            'decision' => 'approve',
        ]);

        $reviewResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'published',
            ]);

        // Verificar que artigo foi publicado
        $this->assertDatabaseHas('articles', [
            'id' => $articleId,
            'status' => 'publicado',
            'reviewed_by' => $user->id,
            'published_at' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function it_can_execute_orchestration_and_reject_article()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'symbol' => 'VALE3',
                                            'price' => 65.00,
                                        ])
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => '<h1>Artigo</h1><p>Conteúdo...</p>'
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200),
            'newsapi.org/*' => Http::response(['articles' => []], 200),
        ]);

        // Executar orquestração
        $response = $this->postJson('/api/orchestrate', [
            'company_name' => 'Vale',
        ]);

        $articleId = $response->json('article_id');

        // Rejeitar artigo
        $user = User::factory()->create();
        $token = $this->generateJwtToken($user);
        $reviewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/orchestrate/{$articleId}/review", [
            'decision' => 'reject',
            'motivo_reprovacao' => 'Conteúdo não atende aos padrões editoriais',
        ]);

        $reviewResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'rejected',
            ]);

        // Verificar que artigo foi rejeitado
        $this->assertDatabaseHas('articles', [
            'id' => $articleId,
            'status' => 'reprovado',
            'motivo_reprovacao' => 'Conteúdo não atende aos padrões editoriais',
        ]);
    }

    /** @test */
    public function it_creates_stock_symbol_if_not_exists()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'symbol' => 'ITUB4',
                                            'company_name' => 'Itaú Unibanco',
                                            'price' => 25.00,
                                        ])
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => '<h1>Artigo</h1>'
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200),
            'newsapi.org/*' => Http::response(['articles' => []], 200),
        ]);

        // Verificar que símbolo não existe
        $this->assertDatabaseMissing('stock_symbols', [
            'symbol' => 'ITUB4',
        ]);

        // Executar orquestração
        $response = $this->postJson('/api/orchestrate', [
            'company_name' => 'Itaú',
        ]);

        $response->assertStatus(200);

        // Verificar que símbolo foi criado
        $this->assertDatabaseHas('stock_symbols', [
            'symbol' => 'ITUB4',
            'company_name' => 'Itaú Unibanco',
        ]);
    }

    /** @test */
    public function it_handles_errors_during_orchestration_gracefully()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/orchestrate', [
            'company_name' => 'Petrobras',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);

        // Verificar que logs foram retornados
        $this->assertArrayHasKey('logs', $response->json());
    }
}

