<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * HTTP Kernel - Gerencia middleware e requisições HTTP
 * 
 * Configurado para suportar os agentes de IA:
 * - Júlia: coleta de dados financeiros
 * - Pedro: análise de sentimento
 * - Key: geração de matérias
 * - PublishNotify: notificações
 * - Cleanup: limpeza e manutenção
 */
class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     * Ordem importa: middleware são executados na sequência declarada.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // TrustHosts: define hosts confiáveis (desabilitado por padrão)
        // \App\Http\Middleware\TrustHosts::class,
        
        // TrustProxies: confia em proxies reversos (load balancers, etc)
        \App\Http\Middleware\TrustProxies::class,
        
        // CORS: permite requisições cross-origin para APIs
        \Fruitcake\Cors\HandleCors::class,
        
        // PreventRequestsDuringMaintenance: bloqueia requisições durante manutenção
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        
        // ValidatePostSize: valida tamanho de requisições POST
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        
        // TrimStrings: remove espaços em branco do início/fim de strings
        \App\Http\Middleware\TrimStrings::class,
        
        // ConvertEmptyStringsToNull: converte strings vazias em null
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * Middleware groups são aplicados automaticamente a grupos de rotas.
     * 
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // Middleware para rotas web (sessões, cookies, CSRF)
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // Middleware para rotas API
            // Sanctum: autenticação stateless (opcional)
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            
            // Rate limiting: limita requisições por minuto (configurado em RouteServiceProvider)
            // Padrão: 60 requisições por minuto por IP/usuário
            'throttle:api',
            
            // SubstituteBindings: injeta automaticamente models nas rotas
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        // Grupo específico para agentes de IA (com rate limiting mais restritivo)
        'agents' => [
            'throttle:agents', // Rate limiting específico para agentes (configurado em RouteServiceProvider)
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     * Pode ser usado em rotas individuais: Route::middleware('auth')
     * 
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        // Autenticação padrão do Laravel
        'auth' => \App\Http\Middleware\Authenticate::class,
        
        // Autenticação HTTP Basic
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        
        // Cache headers: define headers de cache HTTP
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        
        // Autorização: verifica permissões usando Policies
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        
        // Guest: permite acesso apenas para usuários não autenticados
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        
        // Password confirmation: requer confirmação de senha
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        
        // Signed URLs: valida assinatura de URLs temporárias
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        
        // Throttle: rate limiting (limite de requisições)
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        
        // Email verification: requer email verificado
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        
        // Middleware customizados do projeto
        // TokenMiddleware: validação de token customizada
        'TokenMiddleware' => \App\Http\Middleware\TokenMiddleware::class,
        
        // JWTToken: validação de token JWT
        'JWTToken' => \App\Http\Middleware\JWTToken::class,
    ];
}
