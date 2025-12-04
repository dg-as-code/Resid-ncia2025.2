<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Kernel do Console para o projeto:
 *
 * Proposta:
 *  Criar um grupo de agentes de inteligência artificial para analisar ações e gerar conteúdos
 *  recomendando (ou não) a compra, com base em dados reais e percepção de mercado.
 *
 *  - Júlia: coleta os dados financeiros atualizados (ex.: via Yahoo Finance).
 *  - Pedro: analisa o que o mercado e a mídia estão dizendo sobre a empresa (sentimento, trending).
 *  - Key: jornalista experiente que redige o conteúdo final, com base nos dados dos outros dois.
 *  - Fator humano: revisa e aprova o conteúdo antes da publicação.
 *
 * Objetivo:
 *  Produzir matérias financeiras claras, confiáveis e baseadas em dados, com apoio de IA e curadoria humana.
 *
 * Tecnologias:
 *  - Inteligência Artificial (assistentes, NLP, modelos - p. ex. via Python/serviços externos)
 *  - HTML, CSS, JavaScript (frontend)
 *  - Laravel + PHP (backend, orquestração)
 *  - Python (modelos/ETL ou containers que façam inferência/collect)
 *
 * Arquitetura (sugestão):
 *  - Comandos Artisan / Jobs / Queues para cada etapa (coleta, análise, redação, notificação).
 *  - Júlia e Pedro podem ser implementados como Jobs que chamam serviços externos (APIs / Python microservices).
 *  - Key pode ser um serviço que consolida as saídas e gera um rascunho (p. ex. usando um LLM Gemini via API).
 *  - Fator humano: notificação por e-mail / dashboard para revisão; após aprovação, publica-se no CMS.
 *
 * Observações de segurança e compliance:
 *  - Registre e audite todas as fontes de dados.
 *  - Deixe claro no conteúdo quando a IA contribuiu e quando houve revisão humana.
 *  - Trate com cuidado dados sensíveis e limite recomendações explícitas de investimento (aviso legal).
 *
 * Como usar:
 *  - Crie os comandos listados em $commands (ex: php artisan make:command AgentJuliaFetch)
 *  - Implemente Jobs/Services para cada agente e integre com filas (redis/rabbitmq).
 *  - Ajuste a programação (Schedule) conforme necessidade.
 *
 */

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * Aqui registramos comandos "stubs" que você deve implementar.
     *
     * Comandos sugeridos:
     *  - agent:julia:fetch         -> coleta dados de mercado (Yahoo Finance, etc.)
     *  - agent:pedro:analyze       -> análise de mídia e sentimento
     *  - agent:key:compose         -> gera rascunho de matéria com base nos inputs
     *  - agent:publish:notify      -> cria artefato e notifica revisor humano (Fator humano)
     *  - agent:cleanup             -> rotina de limpeza/archives
     */
    protected $commands = [
        // Registre aqui suas classes de comando (crie-as com php artisan make:command)
        \App\Console\Commands\AgentJuliaFetch::class,
        \App\Console\Commands\AgentPedroAnalyze::class,
        \App\Console\Commands\AgentKeyCompose::class,
        \App\Console\Commands\AgentPublishNotify::class,
        \App\Console\Commands\AgentCleanup::class,
    ];

    /**
     * Define o schedule de execução dos agentes.
     *
     * Ajuste a freqüência conforme necessidade e limites de API (ex.: Yahoo Finance).
     */
    protected function schedule(Schedule $schedule)
    {
        // 1) Júlia: coleta dados de mercado frequentemente.
        // Ex: a cada 5 minutos durante o pregão; ou a cada 30 min fora do horário.
        // Aqui usamos uma configuração simples: a cada 10 minutos.
        $schedule->command('agent:julia:fetch')
                 ->everyTenMinutes()
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/agent_julia.log'));

        // 2) Pedro: análises de mídia e sentimento — roda um pouco menos frequentemente.
        $schedule->command('agent:pedro:analyze')
                 ->hourly()
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/agent_pedro.log'));

        // 3) Key: compõe rascunhos a partir dos insumos (pode rodar sempre que houver novos dados).
        // Ex: roda a cada 30 minutos, ou pode ser acionado por evento na criação de novos inputs.
        $schedule->command('agent:key:compose')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/agent_key.log'));

        // 4) Notificação para revisão humana (Fator humano): por cron diário ou acionada por eventos.
        // Aqui: verifica itens pendentes a cada 15 minutos.
        $schedule->command('agent:publish:notify')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/agent_notify.log'));

        // 5) Manutenção: limpeza de arquivos temporários / old caches.
        $schedule->command('agent:cleanup')
                 ->daily()
                 ->at('03:00')
                 ->appendOutputTo(storage_path('logs/agent_cleanup.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}