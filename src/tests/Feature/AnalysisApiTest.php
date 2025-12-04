<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\User;
use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_request_new_analysis()
    {
        Bus::fake();
        
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/analyses', [
                'company_name' => 'Petrobras',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'analysis' => [
                    'id',
                    'company_name',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('analyses', [
            'company_name' => 'Petrobras',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        Bus::assertChained([
            \App\Jobs\FetchFinancialDataJob::class,
            \App\Jobs\AnalyzeMarketSentimentJob::class,
            \App\Jobs\DraftArticleJob::class,
            \App\Jobs\NotifyReviewerJob::class,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/analyses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    /** @test */
    public function it_sanitizes_company_name_input()
    {
        Bus::fake();
        
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/analyses', [
                'company_name' => '<script>alert("xss")</script>Petrobras',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('analyses', [
            'company_name' => 'Petrobras',
        ]);
    }

    /** @test */
    public function it_can_list_all_analyses()
    {
        $user = User::factory()->create();
        Analysis::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/analyses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_name',
                        'status',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_filter_analyses_by_status()
    {
        $user = User::factory()->create();
        Analysis::factory()->create([
            'status' => 'pending',
            'user_id' => $user->id,
        ]);
        Analysis::factory()->create([
            'status' => 'completed',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/analyses?status=pending');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function it_can_show_specific_analysis()
    {
        $user = User::factory()->create();
        $stockSymbol = StockSymbol::factory()->create();
        $financialData = FinancialData::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);
        $sentimentAnalysis = SentimentAnalysis::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);
        $article = Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
        ]);

        $analysis = Analysis::factory()->create([
            'user_id' => $user->id,
            'stock_symbol_id' => $stockSymbol->id,
            'financial_data_id' => $financialData->id,
            'sentiment_analysis_id' => $sentimentAnalysis->id,
            'article_id' => $article->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/analyses/{$analysis->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $analysis->id,
                'company_name' => $analysis->company_name,
            ])
            ->assertJsonStructure([
                'user',
                'stockSymbol',
                'financialData',
                'sentimentAnalysis',
                'article',
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/analyses', [
            'company_name' => 'Petrobras',
        ]);

        $response->assertStatus(401);
    }
}

