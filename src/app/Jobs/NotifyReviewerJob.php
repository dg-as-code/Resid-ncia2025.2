<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job: Agente PublishNotify - Notificação para Revisão
 * 
 * FLUXO DOS AGENTES:
 * Este job representa o Agente PublishNotify, responsável por notificar o editor
 * quando uma matéria está pronta para revisão humana.
 * 
 * Fluxo:
 * 1. Recebe Article gerado pelo Agente Key (via Analysis->article)
 * 2. Busca email do revisor (variável de ambiente REVIEWER_EMAIL)
 * 3. Envia email de notificação com link para revisão
 * 4. Marca artigo como notificado (notified_at)
 * 
 * Status do artigo:
 * - 'pendente_revisao': Aguardando revisão humana
 * - Após revisão: 'aprovado' ou 'reprovado'
 * - Se aprovado: 'publicado'
 * 
 * Próximo passo: Revisão humana (via ArticleController::approve/reject)
 * 
 * NOTA: Este job não falha a análise se a notificação falhar (não crítico)
 */
class NotifyReviewerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $analysis;

    /**
     * Create a new job instance.
     */
    public function __construct(Analysis $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $article = $this->analysis->article;

            if (!$article) {
                throw new \Exception("Artigo não encontrado para análise {$this->analysis->id}");
            }

            // Busca email do revisor
            $reviewerEmail = env('REVIEWER_EMAIL');
            
            if (!$reviewerEmail) {
                Log::warning('NotifyReviewerJob: REVIEWER_EMAIL não configurado');
                return;
            }

            // Envia email de notificação
            Mail::send('emails.article-pending-review', [
                'article' => $article,
                'analysis' => $this->analysis,
            ], function ($message) use ($reviewerEmail, $article) {
                $message->to($reviewerEmail)
                        ->subject("Nova matéria pendente de revisão: {$article->title}");
            });

            // Marca artigo como notificado
            $article->update(['notified_at' => now()]);

            Log::info('NotifyReviewerJob: Notificação enviada', [
                'analysis_id' => $this->analysis->id,
                'article_id' => $article->id,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyReviewerJob: Erro ao enviar notificação', [
                'analysis_id' => $this->analysis->id,
                'error' => $e->getMessage(),
            ]);

            // Não falha a análise se a notificação falhar
        }
    }
}

