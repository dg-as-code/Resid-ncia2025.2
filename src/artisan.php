#!/usr/bin/env php
<?php

/*
|--------------------------------------------------------------------------
| Artisan - Console Application Entry Point
|--------------------------------------------------------------------------
|
| Este arquivo é o ponto de entrada para todos os comandos Artisan.
| Permite executar comandos via linha de comando, incluindo os agentes de IA.
|
| Sistema de Agentes de IA:
| - Júlia: coleta dados financeiros (OpenAI API)
| - Pedro: análise de sentimento de mercado e mídia
| - Key: geração de matérias financeiras usando LLM
| - PublishNotify: notificações para revisão humana
| - Cleanup: limpeza e manutenção do sistema
|
| Comandos dos Agentes:
| - agent:julia:fetch - Executa Agente Júlia
| - agent:pedro:analyze - Executa Agente Pedro
| - agent:key:compose - Executa Agente Key
| - agent:publish:notify - Executa Agente PublishNotify
| - agent:cleanup - Executa Agente Cleanup
|
*/

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Registrar Auto Loader
|--------------------------------------------------------------------------
|
| O Composer fornece um carregador de classes gerado automaticamente
| para nossa aplicação. Apenas precisamos utilizá-lo! Vamos requerê-lo
| neste script para que não precisemos carregar manualmente nossas
| classes. Isso inclui todas as classes dos agentes de IA.
|
*/

require __DIR__.'/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Carregar Aplicação
|--------------------------------------------------------------------------
|
| Carrega a instância da aplicação Laravel que será usada para executar
| os comandos Artisan, incluindo os comandos dos agentes de IA.
|
*/

$app = require_once __DIR__.'/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Executar Aplicação Artisan
|--------------------------------------------------------------------------
|
| Quando executamos a aplicação de console, o comando CLI atual será
| executado neste console e a resposta será enviada de volta para um
| terminal ou outro dispositivo de saída para os desenvolvedores.
|
| Isso inclui a execução de todos os comandos dos agentes de IA.
|
*/

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

/*
|--------------------------------------------------------------------------
| Finalizar Aplicação
|--------------------------------------------------------------------------
|
| Uma vez que o Artisan terminou de executar, vamos disparar os eventos
| de shutdown para que qualquer trabalho final possa ser feito pela
| aplicação antes de encerrarmos o processo. Esta é a última coisa que
| acontece na requisição.
|
*/

$kernel->terminate($input, $status);

exit($status);
