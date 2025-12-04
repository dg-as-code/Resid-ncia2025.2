<?php

use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| Rotas Principais
|--------------------------------------------------------------------------
*/

// Dashboard principal
Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Artigos
Route::get('/articles', function () {
    return view('articles.index');
})->name('articles.index');

Route::get('/articles/review', function () {
    return view('articles.review');
})->name('articles.review');

Route::get('/articles/{id}', function ($id) {
    return view('articles.show', ['id' => $id]);
})->name('articles.show');

// Dados Financeiros
Route::get('/financial-data', function () {
    return view('financial-data.index');
})->name('financial-data.index');

// Análise de Sentimento
Route::get('/sentiment', function () {
    return view('sentiment.index');
})->name('sentiment.index');

// Controle de Agentes
Route::get('/agents/control', function () {
    return view('agents.control');
})->name('agents.control');

// Orquestração Completa
Route::get('/orchestrate', function () {
    return view('orchestrate');
})->name('orchestrate');

/*
|--------------------------------------------------------------------------
| Rotas Legadas (Removidas - arquivo front.html não existe mais)
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Rotas de Desenvolvimento (Removidas - não utilizadas)
|--------------------------------------------------------------------------
*/
