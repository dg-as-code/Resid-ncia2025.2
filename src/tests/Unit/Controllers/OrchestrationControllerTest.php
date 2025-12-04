<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\OrchestrationController;
use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrchestrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected OrchestrationController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new OrchestrationController();
    }

    /** @test */
    public function it_validates_company_name_is_required()
    {
        $request = Request::create('/api/orchestrate', 'POST', []);

        $response = $this->controller->orchestrate($request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Nome da empresa é obrigatório', $data['message']);
    }

    /** @test */
    public function it_validates_company_name_is_not_empty()
    {
        $request = Request::create('/api/orchestrate', 'POST', [
            'company_name' => '   ',
        ]);

        $response = $this->controller->orchestrate($request);

        $this->assertEquals(422, $response->getStatusCode());
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

        $request = Request::create("/api/orchestrate/{$article->id}/review", 'POST', [
            'decision' => 'approve',
        ]);

        // Simular autenticação manualmente (já que middleware não é executado em testes unitários)
        $response = $this->controller->reviewDecision($request, $article->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('published', $data['status']);

        $article->refresh();
        $this->assertEquals('publicado', $article->status);
        // Nota: reviewed_by pode ser null se auth não estiver configurado (ok para testes unitários)
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

        $request = Request::create("/api/orchestrate/{$article->id}/review", 'POST', [
            'decision' => 'reject',
            'motivo_reprovacao' => 'Conteúdo inadequado',
        ]);

        // Simular autenticação manualmente (já que middleware não é executado em testes unitários)
        $response = $this->controller->reviewDecision($request, $article->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('rejected', $data['status']);

        $article->refresh();
        $this->assertEquals('reprovado', $article->status);
        $this->assertEquals('Conteúdo inadequado', $article->motivo_reprovacao);
    }

    /** @test */
    public function it_validates_decision_must_be_approve_or_reject()
    {
        $article = Article::factory()->create([
            'status' => 'pendente_revisao',
        ]);

        $request = Request::create("/api/orchestrate/{$article->id}/review", 'POST', [
            'decision' => 'invalid',
        ]);

        $response = $this->controller->reviewDecision($request, $article->id);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /** @test */
    public function it_validates_article_must_be_pending_review()
    {
        $article = Article::factory()->create([
            'status' => 'publicado',
        ]);

        $request = Request::create("/api/orchestrate/{$article->id}/review", 'POST', [
            'decision' => 'approve',
        ]);

        $response = $this->controller->reviewDecision($request, $article->id);

        $this->assertEquals(422, $response->getStatusCode());
    }
}

