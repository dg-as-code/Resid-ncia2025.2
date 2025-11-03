<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\AgendaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Este arquivo contém todas as rotas da API da aplicação.
| Todas as rotas são prefixadas com /api automaticamente.
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
| Autenticação
|--------------------------------------------------------------------------
*/

// Rota de autenticação JWT
// Retorna: 200 (sucesso) ou 401 (não autorizado)
Route::post('/user', [TokenController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Agenda (Mantidas para compatibilidade)
|--------------------------------------------------------------------------
*/

// Rotas da Agenda - mantidas para compatibilidade
// GET /api/agenda - Lista todas as agendas (200)
// POST /api/agenda - Cria nova agenda (201)
// GET /api/agenda/{id} - Visualiza agenda específica (200 ou 404)
// PUT /api/agenda/{id} - Atualiza agenda (200 ou 404)
// DELETE /api/agenda/{id} - Deleta agenda (200 ou 404)
Route::get('/agenda', [AgendaController::class, 'index']);
Route::post('/agenda', [AgendaController::class, 'criar']);
Route::get('/agenda/{id}', [AgendaController::class, 'visualizar']);
Route::put('/agenda/{id}', [AgendaController::class, 'atualizar']);
Route::delete('/agenda/{id}', [AgendaController::class, 'deletar']);

/*
|--------------------------------------------------------------------------
| Agentes de IA
|--------------------------------------------------------------------------
|
| Rotas para execução e monitoramento dos agentes de IA.
| Usa middleware group 'agents' com rate limiting específico (10 req/min).
|
*/

Route::prefix('agents')->middleware('agents')->group(function () {
    // Status dos agentes (sem rate limiting restritivo)
    // GET /api/agents/status - Retorna status de todos os agentes
    Route::get('/status', [App\Http\Controllers\AgentController::class, 'status'])
        ->withoutMiddleware('throttle:agents');
    
    // Execução dos agentes (com rate limiting)
    // POST /api/agents/julia - Executa Agente Júlia (coleta dados financeiros)
    Route::post('/julia', [App\Http\Controllers\AgentController::class, 'runJulia']);
    
    // POST /api/agents/pedro - Executa Agente Pedro (análise de sentimento)
    Route::post('/pedro', [App\Http\Controllers\AgentController::class, 'runPedro']);
    
    // POST /api/agents/key - Executa Agente Key (gera matérias financeiras)
    Route::post('/key', [App\Http\Controllers\AgentController::class, 'runKey']);
    
    // POST /api/agents/publish-notify - Executa Agente PublishNotify (notificações)
    Route::post('/publish-notify', [App\Http\Controllers\AgentController::class, 'runPublishNotify']);
    
    // POST /api/agents/cleanup - Executa Agente Cleanup (limpeza e manutenção)
    Route::post('/cleanup', [App\Http\Controllers\AgentController::class, 'runCleanup']);
});

/*
|--------------------------------------------------------------------------
| Ações Monitoradas (Stock Symbols)
|--------------------------------------------------------------------------
|
| Rotas para gerenciar ações monitoradas pelo sistema.
| Requer autenticação JWT para operações de escrita.
|
*/

Route::prefix('stock-symbols')->group(function () {
    // GET /api/stock-symbols - Lista todas as ações monitoradas
    Route::get('/', [App\Http\Controllers\StockSymbolController::class, 'index']);
    
    // POST /api/stock-symbols - Cria nova ação monitorada (requer autenticação)
    Route::post('/', [App\Http\Controllers\StockSymbolController::class, 'store'])
        ->middleware('JWTToken');
    
    // GET /api/stock-symbols/{id} - Visualiza ação específica
    Route::get('/{id}', [App\Http\Controllers\StockSymbolController::class, 'show']);
    
    // PUT /api/stock-symbols/{id} - Atualiza ação (requer autenticação)
    Route::put('/{id}', [App\Http\Controllers\StockSymbolController::class, 'update'])
        ->middleware('JWTToken');
    
    // DELETE /api/stock-symbols/{id} - Deleta ação (requer autenticação)
    Route::delete('/{id}', [App\Http\Controllers\StockSymbolController::class, 'destroy'])
        ->middleware('JWTToken');
});

/*
|--------------------------------------------------------------------------
| Dados Financeiros
|--------------------------------------------------------------------------
|
| Rotas para consultar dados financeiros coletados pelo Agente Júlia.
| Apenas leitura (não requer autenticação).
|
*/

Route::prefix('financial-data')->group(function () {
    // GET /api/financial-data - Lista todos os dados financeiros
    Route::get('/', [App\Http\Controllers\FinancialDataController::class, 'index']);
    
    // GET /api/financial-data/{id} - Visualiza dado financeiro específico
    Route::get('/{id}', [App\Http\Controllers\FinancialDataController::class, 'show']);
    
    // GET /api/financial-data/symbol/{symbol}/latest - Último dado financeiro de uma ação
    Route::get('/symbol/{symbol}/latest', [App\Http\Controllers\FinancialDataController::class, 'latest']);
});

/*
|--------------------------------------------------------------------------
| Análise de Sentimento
|--------------------------------------------------------------------------
|
| Rotas para consultar análises de sentimento realizadas pelo Agente Pedro.
| Apenas leitura (não requer autenticação).
|
*/

Route::prefix('sentiment-analysis')->group(function () {
    // GET /api/sentiment-analysis - Lista todas as análises de sentimento
    Route::get('/', [App\Http\Controllers\SentimentAnalysisController::class, 'index']);
    
    // GET /api/sentiment-analysis/{id} - Visualiza análise específica
    Route::get('/{id}', [App\Http\Controllers\SentimentAnalysisController::class, 'show']);
    
    // GET /api/sentiment-analysis/symbol/{symbol}/latest - Última análise de uma ação
    Route::get('/symbol/{symbol}/latest', [App\Http\Controllers\SentimentAnalysisController::class, 'latest']);
});

/*
|--------------------------------------------------------------------------
| Artigos/Matérias
|--------------------------------------------------------------------------
|
| Rotas para gerenciar artigos/matérias geradas pelo Agente Key.
| Requer autenticação JWT para aprovar/reprovar/publicar.
|
*/

Route::prefix('articles')->group(function () {
    // GET /api/articles - Lista todos os artigos
    Route::get('/', [App\Http\Controllers\ArticleController::class, 'index']);
    
    // GET /api/articles/{id} - Visualiza artigo específico
    Route::get('/{id}', [App\Http\Controllers\ArticleController::class, 'show']);
    
    // POST /api/articles/{id}/approve - Aprova artigo (requer autenticação e permissão)
    Route::post('/{id}/approve', [App\Http\Controllers\ArticleController::class, 'approve'])
        ->middleware(['JWTToken', 'can:approve,article']);
    
    // POST /api/articles/{id}/reject - Reprova artigo (requer autenticação e permissão)
    Route::post('/{id}/reject', [App\Http\Controllers\ArticleController::class, 'reject'])
        ->middleware(['JWTToken', 'can:reject,article']);
    
    // POST /api/articles/{id}/publish - Publica artigo (requer autenticação e permissão)
    Route::post('/{id}/publish', [App\Http\Controllers\ArticleController::class, 'publish'])
        ->middleware(['JWTToken', 'can:publish,article']);
    
    // DELETE /api/articles/{id} - Deleta artigo (requer autenticação e permissão)
    Route::delete('/{id}', [App\Http\Controllers\ArticleController::class, 'destroy'])
        ->middleware(['JWTToken', 'can:delete,article']);
});
