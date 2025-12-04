<?php

namespace Tests\Unit\Commands;

use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCleanupTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_runs_cleanup_successfully()
    {
        // Cria dados antigos
        FinancialData::factory()->count(5)->create();
        SentimentAnalysis::factory()->count(3)->create();
        Article::factory()->count(2)->create();

        $this->artisan('agent:cleanup')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_empty_database()
    {
        $this->artisan('agent:cleanup')
            ->assertExitCode(0);
    }
}

