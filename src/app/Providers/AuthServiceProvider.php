<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\StockSymbol;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\StockSymbolPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * AuthServiceProvider - Registra policies e gates de autorização
 * 
 * Configurado para suportar os agentes de IA e controle de acesso
 * aos recursos do sistema.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * Mapeia models para suas respectivas policies.
     * 
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Article::class => ArticlePolicy::class,
        StockSymbol::class => StockSymbolPolicy::class,
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gates personalizados para funcionalidades específicas
        $this->defineGates();
    }

    /**
     * Define gates personalizados para autorização
     * 
     * @return void
     */
    protected function defineGates(): void
    {
        // Gate para executar agentes de IA
        Gate::define('execute-agent', function (User $user) {
            // Todos os usuários autenticados podem executar agentes
            // Você pode restringir isso se necessário
            return true;
        });

        // Gate para revisar artigos
        Gate::define('review-articles', function (User $user) {
            return $user->isReviewer();
        });

        // Gate para gerenciar ações monitoradas
        Gate::define('manage-stock-symbols', function (User $user) {
            return $user->isReviewer();
        });
    }
}
