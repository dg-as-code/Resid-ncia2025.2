<?php

/*
|--------------------------------------------------------------------------
| Bootstrap da Aplicação Laravel
|--------------------------------------------------------------------------
|
| Este arquivo é responsável por inicializar a aplicação Laravel.
| Cria a instância da aplicação e configura os componentes principais.
|
| Sistema de Agentes de IA:
| - Júlia: coleta dados financeiros (Yahoo Finance)
| - Pedro: análise de sentimento de mercado e mídia
| - Key: geração de matérias financeiras usando LLM
| - PublishNotify: notificações para revisão humana
| - Cleanup: limpeza e manutenção do sistema
|
*/

/*
|--------------------------------------------------------------------------
| Criar Instância da Aplicação
|--------------------------------------------------------------------------
|
| Cria uma nova instância da aplicação Laravel que serve como o "cola"
| para todos os componentes do Laravel, e é o container IoC para o
| sistema, vinculando todas as partes.
|
| O caminho base pode ser configurado via variável de ambiente APP_BASE_PATH.
| Por padrão, usa o diretório pai do bootstrap.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind de Interfaces Importantes
|--------------------------------------------------------------------------
|
| Registra interfaces importantes no container de injeção de dependência
| para que possam ser resolvidas quando necessário.
|
| Os kernels servem as requisições recebidas para esta aplicação tanto
| da web quanto da CLI (Artisan).
|
| 1. HTTP Kernel: processa requisições HTTP
| 2. Console Kernel: processa comandos Artisan (incluindo os agentes de IA)
| 3. Exception Handler: trata exceções e erros da aplicação
|
*/

// HTTP Kernel - Gerencia requisições HTTP e middleware
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

// Console Kernel - Gerencia comandos Artisan e agendamento
// Inclui os comandos dos agentes de IA (agent:julia:fetch, agent:pedro:analyze, etc)
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

// Exception Handler - Trata exceções com tratamento específico para agentes
// Inclui detecção de erros de agentes, APIs externas e Python/LLM
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Retornar Instância da Aplicação
|--------------------------------------------------------------------------
|
| Retorna a instância da aplicação. A instância é fornecida ao script
| chamador para que possamos separar a construção das instâncias da
| execução real da aplicação e envio de respostas.
|
| Esta separação permite:
| - Testes unitários e de integração
| - Execução via diferentes pontos de entrada (web, CLI, queue workers)
| - Melhor organização do código
|
*/

return $app;
