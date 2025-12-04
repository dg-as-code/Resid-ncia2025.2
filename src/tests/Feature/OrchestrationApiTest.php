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
 * Testes de Integração para Orquestração Completa
 * 
 * Testa o fluxo completo: Júlia → Pedro → Key → PublishNotify → Revisão Humana
 */
class OrchestrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar API keys para testes
        config([
            'services.llm.gemini.api_key' => 'test-api-key',
            'services.llm.provider' => 'gemini',
        ]);
    }

    /** @test */
    public function it_requires_company_name_to_orchestrate()
    {
        $response = $this->postJson('/api/orchestrate', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Nome da empresa é obrigatório',
            ]);
    }

    /** @test */
    public function it_can_execute_full_orchestration_flow()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                // Resposta para Júlia (dados financeiros)
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
                                        ])
                                    ]
                                ]
                            ],
                            'finishReason' => 'STOP'
                        ]
                    ]
                ], 200)
                // Resposta para Key (artigo)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => '<h1>Análise Petrobras</h1><p>Conteúdo do artigo...</p>'
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
                        'title' => 'Petrobras anuncia resultados',
                        'description' => 'Petrobras reporta lucro recorde',
                        'publishedAt' => now()->toIso8601String(),
                    ]
                ]
            ], 200),
        ]);

        $response = $this->postJson('/api/orchestrate', [
            'company_name' => 'Petrobras',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status',
                'message',
                'article_id',
                'article' => [
                    'id',
                    'title',
                    'html_content',
                    'symbol',
                    'status',
                ],
                'financial_data',
                'sentiment_data',
                'logs',
            ]);

        $this->assertEquals('pending_review', $response->json('status'));
        $this->assertDatabaseHas('articles', [
            'status' => 'pendente_revisao',
        ]);
    }

    /** @test */
    public function it_returns_logs_from_orchestration()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'symbol' => 'PETR4',
                                        'price' => 30.50,
                                    ])
                                ]
                            ]
                        ],
                        'finishReason' => 'STOP'
                    ]
                ]
            ], 200),
            'newsapi.org/*' => Http::response(['articles' => []], 200),
        ]);

        $response = $this->postJson('/api/orchestrate', [
            'company_name' => 'Petrobras',
        ]);

        $response->assertStatus(200);
        $logs = $response->json('logs');
        
        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
        
        // Verifica que há logs de cada agente
        $agentNames = array_column($logs, 'agent');
        $this->assertContains('Sistema', $agentNames);
        $this->assertContains('Julia', $agentNames);
    }

    /** @test */
    public function it_can_process_review_decision_approve()
    {
        $user = User::factory()->create();
        $stockSymbol = StockSymbol::factory()->create(['symbol' => 'PETR4']);
        $financialData = FinancialData::factory()->create(['stock_symbol_id' => $stockSymbol->id]);
        $sentimentData = SentimentAnalysis::factory()->create(['stock_symbol_id' => $stockSymbol->id]);
        
        $article = Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'status' => 'pendente_revisao',
            'financial_data_id' => $financialData->id,
            'sentiment_analysis_id' => $sentimentData->id,
        ]);

        $token = $this->generateJwtToken($user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/orchestrate/{$article->id}/review", [
            'decision' => 'approve',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'published',
            ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => 'publicado',
            'reviewed_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_process_review_decision_reject()
    {
        $user = User::factory()->create();
        $stockSymbol = StockSymbol::factory()->create(['symbol' => 'PETR4']);
        $financialData = FinancialData::factory()->create(['stock_symbol_id' => $stockSymbol->id]);
        $sentimentData = SentimentAnalysis::factory()->create(['stock_symbol_id' => $stockSymbol->id]);
        
        $article = Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'status' => 'pendente_revisao',
            'financial_data_id' => $financialData->id,
            'sentiment_analysis_id' => $sentimentData->id,
        ]);

        $token = $this->generateJwtToken($user);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/orchestrate/{$article->id}/review", [
            'decision' => 'reject',
            'motivo_reprovacao' => 'Conteúdo inadequado',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'rejected',
            ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => 'reprovado',
            'motivo_reprovacao' => 'Conteúdo inadequado',
            'reviewed_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_requires_motivo_when_rejecting()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'status' => 'pendente_revisao',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/orchestrate/{$article->id}/review", [
                'decision' => 'reject',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Motivo da reprovação é obrigatório',
            ]);
    }

    /** @test */
    public function it_handles_errors_gracefully_during_orchestration()
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
    }
}

