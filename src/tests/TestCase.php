<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

/**
 * TestCase - Classe base para todos os testes
 * 
 * Sistema de Agentes de IA:
 * - Júlia: coleta dados financeiros (Yahoo Finance)
 * - Pedro: análise de sentimento de mercado e mídia
 * - Key: geração de matérias financeiras usando LLM
 * - PublishNotify: notificações para revisão humana
 * - Cleanup: limpeza e manutenção do sistema
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup do ambiente de teste
     * Configura mocks HTTP globais para evitar chamadas reais
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock global para APIs externas (evita chamadas reais)
        Http::fake([
            // Gemini API - Mock padrão
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'symbol' => 'TEST',
                                        'price' => 100.00,
                                        'previous_close' => 99.00,
                                        'change' => 1.00,
                                        'change_percent' => 1.01,
                                    ])
                                ]
                            ]
                        ],
                        'finishReason' => 'STOP'
                    ]
                ]
            ], 200),
            
            // News API - Mock padrão
            'newsapi.org/*' => Http::response([
                'articles' => [
                    [
                        'title' => 'Test News',
                        'description' => 'Test Description',
                        'publishedAt' => now()->toIso8601String(),
                        'url' => 'https://example.com/news',
                    ]
                ]
            ], 200),
        ]);

        // Configurar variáveis de ambiente para testes
        config([
            'services.llm.gemini.api_key' => 'test-api-key',
            'services.llm.gemini.model' => 'gemini-pro',
            'services.llm.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.llm.provider' => 'gemini',
        ]);
    }

    /**
     * Cleanup após cada teste
     */
    protected function tearDown(): void
    {
        // Limpar mocks HTTP
        Http::fake();
        
        parent::tearDown();
    }

    /**
     * Gera token JWT para testes
     * 
     * @param \App\Models\User|null $user
     * @return string
     */
    protected function generateJwtToken($user = null): string
    {
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }

        $key = config('app.jwt_key', env('JWT_KEY', '839457620204846352749e89366553'));
        $payload = [
            'sub' => $user->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hora
        ];

        return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Faz requisição autenticada com JWT
     * 
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param \App\Models\User|null $user
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedJson(string $method, string $uri, array $data = [], array $headers = [], $user = null)
    {
        $token = $this->generateJwtToken($user);
        
        $headers['Authorization'] = 'Bearer ' . $token;
        
        return $this->json($method, $uri, $data, $headers);
    }
}
