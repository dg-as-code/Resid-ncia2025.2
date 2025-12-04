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
| - Júlia: coleta dados financeiros (Yahoo Finance + Gemini API)
| - Pedro: análise de sentimento de mercado e mídia (News API)
| - Key: geração de matérias financeiras usando LLM (Python/Gemini)
| - PublishNotify: notificações para revisão humana
| - Cleanup: limpeza e manutenção do sistema
|
| Comandos dos Agentes:
| - agent:julia:fetch - Executa Agente Júlia (coleta dados financeiros)
| - agent:pedro:analyze - Executa Agente Pedro (análise de sentimento)
| - agent:key:compose - Executa Agente Key (gera matérias financeiras)
| - agent:publish:notify - Executa Agente PublishNotify (notificações)
| - agent:cleanup - Executa Agente Cleanup (limpeza e manutenção)
|
| Configuração:
| - Variáveis de ambiente em .env:
|   * GEMINI_API_KEY - Chave da API Gemini
|   * GEMINI_MODEL - Modelo Gemini a ser usado
|   * NEWS_API_KEY - Chave da News API
|   * LLM_PROVIDER - Provedor LLM (python)
|   * PYTHON_PATH - Caminho do Python
|   * LLM_SCRIPT_PATH - Caminho do script LLM
|
| Uso:
|   php artisan agent:julia:fetch --symbol=Petrobras
|   php artisan agent:pedro:analyze --symbol=Petrobras
|   php artisan agent:key:compose --symbol=Petrobras
|   php artisan agent:publish:notify
|   php artisan agent:cleanup
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
| classes. Isso inclui todas as classes dos agentes de IA, serviços,
| modelos e comandos Artisan.
|
| O autoloader carrega automaticamente:
| - App\Console\Commands\* (comandos dos agentes)
| - App\Services\* (YahooFinanceService, NewsAnalysisService, LLMService)
| - App\Models\* (StockSymbol, FinancialData, SentimentAnalysis, Article)
| - App\Jobs\* (FetchFinancialDataJob, AnalyzeMarketSentimentJob, etc)
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
| O bootstrap/app.php configura:
| - Console Kernel (App\Console\Kernel) - gerencia comandos e agendamento
| - Service Providers - registra serviços e dependências
| - Configurações - carrega arquivos de config/*.php
| - Variáveis de ambiente - lê o arquivo .env
|
| O Console Kernel registra todos os comandos dos agentes e configura
| o agendamento automático (schedule) para execução periódica.
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
| Isso inclui a execução de todos os comandos dos agentes de IA:
| - agent:julia:fetch - Coleta dados financeiros do Yahoo Finance
| - agent:pedro:analyze - Analisa sentimento usando News API
| - agent:key:compose - Gera matérias usando LLM (Python/Gemini)
| - agent:publish:notify - Envia notificações para revisores
| - agent:cleanup - Limpa arquivos temporários e caches
|
| O kernel processa os argumentos da linha de comando, resolve o comando
| apropriado e executa com os parâmetros fornecidos.
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
