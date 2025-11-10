<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Este arquivo é onde você pode definir todos os seus comandos de console
| baseados em Closure. Cada Closure é vinculado a uma instância de comando
| permitindo uma abordagem simples para interagir com os métodos de IO
| de cada comando.
|
| Sistema de Agentes de IA:
| Os agentes são executados via comandos Artisan agendados:
| - agent:julia:fetch - Executa Agente Júlia (coleta dados financeiros)
| - agent:pedro:analyze - Executa Agente Pedro (análise de sentimento)
| - agent:key:compose - Executa Agente Key (gera matérias financeiras)
| - agent:publish:notify - Executa Agente PublishNotify (notificações)
| - agent:cleanup - Executa Agente Cleanup (limpeza e manutenção)
|
| Veja app/Console/Kernel.php para agendamento automático.
|
*/

/*
|--------------------------------------------------------------------------
| Comandos de Exemplo
|--------------------------------------------------------------------------
*/

// Comando inspire - exibe uma citação inspiradora
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
