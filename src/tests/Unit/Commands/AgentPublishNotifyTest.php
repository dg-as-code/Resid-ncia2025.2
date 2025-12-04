<?php

namespace Tests\Unit\Commands;

use App\Models\Article;
use App\Models\StockSymbol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentPublishNotifyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_notifies_reviewers_for_pending_articles()
    {
        Mail::fake();

        $stockSymbol = StockSymbol::factory()->create();
        Article::factory()->count(3)->create([
            'stock_symbol_id' => $stockSymbol->id,
            'status' => 'pendente_revisao',
            'notified_at' => null,
        ]);

        $this->artisan('agent:publish:notify')
            ->assertExitCode(0);

        Mail::assertSent(function ($mail) {
            return true;
        });
    }

    /** @test */
    public function it_does_not_notify_already_notified_articles()
    {
        Mail::fake();

        $stockSymbol = StockSymbol::factory()->create();
        Article::factory()->create([
            'stock_symbol_id' => $stockSymbol->id,
            'status' => 'pendente_revisao',
            'notified_at' => now(),
        ]);

        $this->artisan('agent:publish:notify')
            ->expectsOutput('Nenhuma matéria pendente de revisão.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    /** @test */
    public function it_supports_dry_run_mode()
    {
        $stockSymbol = StockSymbol::factory()->create();
        Article::factory()->count(2)->create([
            'stock_symbol_id' => $stockSymbol->id,
            'status' => 'pendente_revisao',
            'notified_at' => null,
        ]);

        $this->artisan('agent:publish:notify', ['--dry-run' => true])
            ->expectsOutput('Modo dry-run: Notificações não serão enviadas.')
            ->assertExitCode(0);
    }
}

