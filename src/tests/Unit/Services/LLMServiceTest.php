<?php

namespace Tests\Unit\Services;

use App\Services\LLMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class LLMServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LLMService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar provider para Gemini
        config([
            'services.llm.provider' => 'gemini',
            'services.llm.gemini.api_key' => 'test-key',
        ]);
        
        $this->service = new LLMService();
    }

    /** @test */
    public function it_can_generate_article_with_gemini_directly()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Análise Petrobras',
                                        'content' => 'A Petrobras apresentou...',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        config(['services.llm.gemini.api_key' => 'test-key']);

        $financialData = [
            'price' => 30.50,
            'change_percent' => 1.67,
        ];

        $sentimentData = [
            'sentiment' => 'positive',
            'sentiment_score' => 0.75,
        ];

        $result = $this->service->generateArticle($financialData, $sentimentData, 'Petrobras');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('content', $result);
    }

    /** @test */
    public function it_returns_fallback_when_gemini_unavailable()
    {
        config(['services.llm.gemini.api_key' => null]);

        $financialData = [
            'price' => 30.50,
        ];

        $sentimentData = [
            'sentiment' => 'neutral',
        ];

        $result = $this->service->generateArticle($financialData, $sentimentData, 'Petrobras');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('content', $result);
    }

    /** @test */
    public function it_adds_disclaimer_to_generated_article()
    {
        // Quando Gemini não está disponível, usa fallback que inclui disclaimer
        config(['services.llm.gemini.api_key' => null]);
        config(['services.llm.provider' => 'fallback']);

        $financialData = ['price' => 30.50];
        $sentimentData = ['sentiment' => 'neutral'];

        $result = $this->service->generateArticle($financialData, $sentimentData, 'Petrobras');

        // O fallback sempre inclui disclaimer
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringContainsString('disclaimer', strtolower($result['content']));
    }
}
