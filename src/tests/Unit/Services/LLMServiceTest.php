<?php

namespace Tests\Unit\Services;

use App\Services\LLMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class LLMServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LLMService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LLMService();
    }

    /** @test */
    public function it_can_generate_article_content()
    {
        $financialData = [
            'price' => 30.50,
            'change' => 0.50,
            'change_percent' => 1.67,
        ];

        $sentimentData = [
            'sentiment' => 'positive',
            'sentiment_score' => 0.65,
            'news_count' => 10,
        ];

        $result = $this->service->generateArticle(
            $financialData,
            $sentimentData,
            'PETR4'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertNotEmpty($result['title']);
        $this->assertNotEmpty($result['content']);
    }

    /** @test */
    public function it_handles_python_script_error_gracefully()
    {
        Process::fake([
            '*' => Process::result(
                exitCode: 1,
                errorOutput: 'Error executing script'
            ),
        ]);

        $financialData = ['price' => 30.50];
        $sentimentData = ['sentiment' => 'positive'];

        $result = $this->service->generateArticle(
            $financialData,
            $sentimentData,
            'PETR4'
        );

        // Deve retornar fallback mesmo com erro
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
    }

    /** @test */
    public function it_uses_fallback_when_python_not_available()
    {
        config(['services.llm.provider' => 'python']);

        $financialData = ['price' => 30.50];
        $sentimentData = ['sentiment' => 'positive'];

        $result = $this->service->generateArticle(
            $financialData,
            $sentimentData,
            'PETR4'
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['content']);
    }
}

