<?php

/*
|--------------------------------------------------------------------------
| Servidor PHP Built-in - Router
|--------------------------------------------------------------------------
|
| Este arquivo permite emular a funcionalidade "mod_rewrite" do Apache
| usando o servidor web built-in do PHP. Isso fornece uma maneira conveniente
| de testar a aplicação Laravel sem precisar instalar um servidor web "real".
|
| Sistema de Agentes de IA:
| - Júlia: coleta dados financeiros (Yahoo Finance + Gemini API)
| - Pedro: análise de sentimento de mercado e mídia (News API)
| - Key: geração de matérias financeiras usando LLM (Python/Gemini)
| - PublishNotify: notificações para revisão humana
| - Cleanup: limpeza e manutenção do sistema
|
| Endpoints da API disponíveis:
| - /api/agents/* - Execução e status dos agentes
| - /api/stock-symbols/* - Gerenciamento de ações monitoradas
| - /api/financial-data/* - Dados financeiros coletados
| - /api/sentiment-analysis/* - Análises de sentimento
| - /api/articles/* - Artigos/matérias geradas
| - /front - Painel de controle (front.html)
|
| Como usar:
|   php -S localhost:8000 -t public server.php
|
| Ou usando o comando Artisan:
|   php artisan serve
|
| Nota: Este arquivo é usado apenas para desenvolvimento local.
| Em produção, use um servidor web real (Apache/Nginx) configurado
| para apontar para o diretório public/.
|
*/

/*
|--------------------------------------------------------------------------
| Processar URI da Requisição
|--------------------------------------------------------------------------
|
| Decodifica a URI da requisição e verifica se é um arquivo estático.
| Se for um arquivo estático existente no diretório public/, serve-o
| diretamente. Caso contrário, delega para o index.php do Laravel.
|
*/

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

/*
|--------------------------------------------------------------------------
| Servir Arquivos Estáticos
|--------------------------------------------------------------------------
|
| Se a URI não for a raiz ('/') e o arquivo existir no diretório public/,
| retorna false para que o servidor PHP built-in sirva o arquivo diretamente.
| Isso melhora a performance para arquivos estáticos (CSS, JS, imagens, etc).
|
*/

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

/*
|--------------------------------------------------------------------------
| Delegar para o Laravel
|--------------------------------------------------------------------------
|
| Se não for um arquivo estático, delega o processamento para o index.php
| do Laravel, que irá processar a requisição através do framework, incluindo:
| - Rotas da API
| - Middleware
| - Controllers
| - Exception handling
|
*/

require_once __DIR__.'/public/index.php';
