<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Agente PublishNotify: Notificação para revisão humana
 * 
 * Responsabilidade: Verificar matérias pendentes de revisão e notificar revisores humanos
 * 
 * Este comando verifica matérias que estão prontas para revisão e envia notificações
 * para os revisores humanos (Fator humano).
 */
class AgentPublishNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:publish:notify 
                            {--email= : Email específico para notificar (opcional)}
                            {--dry-run : Apenas simular, sem enviar notificações}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agente PublishNotify: Verifica matérias pendentes e notifica revisores humanos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(' Agente PublishNotify verificando matérias pendentes...');

        try {
            $dryRun = $this->option('dry-run');
            $email = $this->option('email');

            // Busca matérias pendentes de revisão que ainda não foram notificadas
            $pendingArticles = Article::pendingReview()
                ->notNotified()
                ->with(['stockSymbol', 'financialData', 'sentimentAnalysis'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($pendingArticles->isEmpty()) {
                $this->info(' Nenhuma matéria pendente de revisão.');
                return Command::SUCCESS;
            }

            $this->info(" Encontradas " . $pendingArticles->count() . " matéria(s) pendente(s) de revisão.");

            if ($dryRun) {
                $this->warn(' Modo dry-run: Notificações não serão enviadas.');
                $this->newLine();
                foreach ($pendingArticles as $article) {
                    $this->line("  - ID: {$article->id} | {$article->symbol} | {$article->title}");
                    $this->line("    Criado em: " . $article->created_at->format('d/m/Y H:i'));
                }
                return Command::SUCCESS;
            }

            // Prepara destinatários
            $recipients = $this->getRecipients($email);

            if (empty($recipients)) {
                $this->warn(' Nenhum destinatário configurado para notificações.');
                Log::warning('Agent PublishNotify: Nenhum destinatário configurado');
                return Command::SUCCESS;
            }

            $notifiedCount = 0;

            // Envia notificações
            foreach ($recipients as $recipient) {
                try {
                    // Prepara dados para o email
                    $subject = " {$pendingArticles->count()} matéria(s) pendente(s) de revisão";
                    $viewData = [
                        'articles' => $pendingArticles,
                        'count' => $pendingArticles->count(),
                    ];

                    // Envia email (você pode criar uma Mailable se preferir)
                    Mail::send('emails.articles-pending-review', $viewData, function ($message) use ($recipient, $subject) {
                        $message->to($recipient)
                                ->subject($subject);
                    });

                    $notifiedCount++;
                    Log::info('Agent PublishNotify: Email enviado', [
                        'recipient' => $recipient,
                        'articles_count' => $pendingArticles->count(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Agent PublishNotify: Erro ao enviar email', [
                        'recipient' => $recipient,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Marca matérias como notificadas
            $now = now();
            Article::whereIn('id', $pendingArticles->pluck('id'))
                ->update(['notified_at' => $now]);

            $this->info(" Notificações enviadas para {$notifiedCount} destinatário(s)!");
            $this->info(" {$pendingArticles->count()} matéria(s) marcada(s) como notificada(s).");

            Log::info('Agent PublishNotify: Notificações enviadas', [
                'articles_count' => $pendingArticles->count(),
                'recipients_count' => $notifiedCount,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error(' Erro ao enviar notificações: ' . $e->getMessage());
            Log::error('Agent PublishNotify: Erro ao enviar notificações', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Obtém lista de destinatários para notificação
     * 
     * @param string|null $email
     * @return array
     */
    protected function getRecipients(?string $email): array
    {
        if ($email) {
            return [$email];
        }

        // Busca emails configurados no .env
        $defaultEmail = env('REVIEWER_EMAIL');
        if ($defaultEmail) {
            return [$defaultEmail];
        }

        // Se não houver email configurado, retorna vazio
        // Em produção, você pode buscar do banco de dados (tabela de revisores)
        return [];
    }
}

