<?php

namespace Tests\Unit\Services;

use App\Services\GeminiResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GeminiResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.llm.gemini.api_key' => 'test-api-key',
            'services.llm.gemini.model' => 'gemini-pro',
            'services.llm.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);
        
        $this->service = new GeminiResponseService();
    }

    /** @test */
    public function it_can_check_if_configured()
    {
        $this->assertTrue($this->service->isConfigured());
    }

    /** @test */
    public function it_returns_false_when_not_configured()
    {
        config(['services.llm.gemini.api_key' => null]);
        
        $service = new GeminiResponseService();
        $this->assertFalse($service->isConfigured());
    }

    /** @test */
    public function it_can_generate_response()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Resposta do Gemini'
                                ]
                            ]
                        ],
                        'finishReason' => 'STOP'
                    ]
                ]
            ], 200),
        ]);

        $result = $this->service->generateResponse('Test prompt');

        $this->assertTrue($result['success']);
        $this->assertEquals('Resposta do Gemini', $result['content']);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $result = $this->service->generateResponse('Test prompt');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_generate_article()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '<h1>Título do Artigo</h1><p>Conteúdo do artigo em HTML.</p>'
                                ]
                            ]
                        ],
                        'finishReason' => 'STOP'
                    ]
                ]
            ], 200),
        ]);

        $financialData = [
            'price' => 30.50,
            'change_percent' => 1.67,
        ];
        
        $sentimentData = [
            'sentiment' => 'positive',
            'sentiment_score' => 0.7,
        ];

        $result = $this->service->generateArticle($financialData, $sentimentData, 'PETR4');

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertNotEmpty($result['content']);
    }

    /** @test */
    public function it_can_convert_markdown_to_html()
    {
        $markdown = "# Título\n\nParágrafo com **negrito** e *itálico*.\n\n- Item 1\n- Item 2";
        
        $html = $this->service->markdownToHtml($markdown);

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('<p>', $html);
        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringContainsString('<em>', $html);
        $this->assertStringContainsString('<ul>', $html);
    }

    /** @test */
    public function it_can_detect_valid_html()
    {
        $html = '<h1>Título</h1><p>Parágrafo</p>';
        $this->assertTrue($this->service->isValidHtml($html));

        $text = 'Apenas texto sem HTML';
        $this->assertFalse($this->service->isValidHtml($text));
    }

    /** @test */
    public function it_handles_empty_content()
    {
        $result = $this->service->generateResponse('');

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_parses_article_response_correctly()
    {
        // Testa parseArticleResponse indiretamente através de generateArticle
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '<h1>Título do Artigo</h1><p>Conteúdo do artigo.</p>'
                                ]
                            ]
                        ],
                        'finishReason' => 'STOP'
                    ]
                ]
            ], 200),
        ]);

        $financialData = ['price' => 30.50];
        $sentimentData = ['sentiment' => 'positive'];
        
        $result = $this->service->generateArticle($financialData, $sentimentData, 'PETR4');

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertNotEmpty($result['title']);
        $this->assertNotEmpty($result['content']);
    }

    /** @test */
    public function it_handles_safety_blocked_content()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'finishReason' => 'SAFETY'
                    ]
                ]
            ], 200),
        ]);

        $result = $this->service->generateResponse('Test prompt');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}

