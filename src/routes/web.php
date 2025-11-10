<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Noticias_DestaquesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Este arquivo contém todas as rotas web da aplicação.
| Essas rotas são carregadas pelo RouteServiceProvider dentro de um grupo
| que contém o middleware group "web". Agora crie algo incrível!
|
| Sistema de Agentes de IA:
| - Júlia: coleta dados financeiros (Yahoo Finance)
| - Pedro: análise de sentimento de mercado e mídia
| - Key: geração de matérias financeiras usando LLM
| - PublishNotify: notificações para revisão humana
| - Cleanup: limpeza e manutenção do sistema
|
| Nota: A aplicação é principalmente uma API REST. As rotas web são
| mínimas e podem ser usadas para documentação, dashboard ou páginas
| de status.
|
*/

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/

// Rota principal - página de boas-vindas
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Rotas de Desenvolvimento (Comentadas)
|--------------------------------------------------------------------------
*/

// Route::get('/cadastrar', [Noticias_DestaquesController::class, 'novo']);
