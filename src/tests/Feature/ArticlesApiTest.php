<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlesApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_articles()
    {
        Article::factory()->count(3)->create();

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'symbol',
                        'title',
                        'status',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_show_specific_article()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'status' => 'pendente_revisao',
        ]);

        $response = $this->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Test Article',
                    'status' => 'pendente_revisao',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_approve_article()
    {
        $article = Article::factory()->create([
            'status' => 'pendente_revisao',
        ]);

        $response = $this->postJson("/api/articles/{$article->id}/approve");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_approve_article_with_authentication()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'status' => 'pendente_revisao',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/articles/{$article->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'aprovado',
                ],
            ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => 'aprovado',
            'reviewed_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_reject_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'status' => 'pendente_revisao',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/articles/{$article->id}/reject", [
                'motivo_reprovacao' => 'Conteúdo inadequado',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'reprovado',
                ],
            ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => 'reprovado',
            'motivo_reprovacao' => 'Conteúdo inadequado',
        ]);
    }
}

