<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Bootstrap da Aplicação Laravel
|--------------------------------------------------------------------------
|
| Este arquivo é o ponto de entrada principal da aplicação web Laravel.
| Todas as requisições HTTP são processadas através deste arquivo.
|
| Sistema de Agentes de IA:
| - Júlia: coleta dados financeiros (Yahoo Finance)
| - Pedro: análise de sentimento de mercado e mídia
| - Key: geração de matérias financeiras usando LLM
| - PublishNotify: notificações para revisão humana
| - Cleanup: limpeza e manutenção do sistema
|
| Endpoints da API:
| - /api/agents/* - Execução e status dos agentes
| - /api/stock-symbols/* - Gerenciamento de ações monitoradas
| - /api/financial-data/* - Dados financeiros coletados
| - /api/sentiment-analysis/* - Análises de sentimento
| - /api/articles/* - Artigos/matérias geradas
|
*/

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Verificar Modo de Manutenção
|--------------------------------------------------------------------------
|
| Se a aplicação estiver em modo de manutenção via comando "down",
| carregamos este arquivo para que qualquer conteúdo pré-renderizado
| possa ser exibido ao invés de iniciar o framework, o que causaria
| uma exceção.
|
| Útil para manutenções programadas e atualizações do sistema.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Registrar Auto Loader
|--------------------------------------------------------------------------
|
| O Composer fornece um carregador de classes gerado automaticamente
| para esta aplicação. Apenas precisamos utilizá-lo! Vamos requerê-lo
| neste script para que não precisemos carregar manualmente nossas classes.
|
| Isso inclui todas as classes dos agentes de IA e serviços.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Executar Aplicação
|--------------------------------------------------------------------------
|
| Uma vez que temos a aplicação, podemos processar a requisição recebida
| usando o kernel HTTP da aplicação. Então, enviamos a resposta de volta
| para o cliente, permitindo que eles usem nossa aplicação.
|
| O kernel HTTP processa:
| - Middleware (autenticação, rate limiting, etc.)
| - Rotas da API (incluindo endpoints dos agentes)
| - Exception handling (tratamento específico para agentes)
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
