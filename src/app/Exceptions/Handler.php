<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * Tipos de exceções que não devem ser reportadas.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        // Adiciona exceções sem login
        ValidationException::class,
        AuthenticationException::class,
    ];

    /**
     * Inputs que nunca devem ser incluídos no flash (em erros de validação).
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registra os callbacks de tratamento de exceções.
     *
     * @return void
     */
    public function register()
    {
        // Callback padrão
        $this->reportable(function (Throwable $e) {
            //
        });

        // Tratamento específico para erros dos agentes de IA
        $this->reportable(function (Throwable $e) {
            if ($this->isAgentError($e)) {
                $agent = $this->detectAgent($e);
                $context = $this->getAgentContext($e, $agent);
                
                Log::channel('daily')->error("[AGENT ERROR] {$agent}: " . $e->getMessage(), [
                    'agent' => $agent,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'context' => $context,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        // Tratamento para erros críticos
        $this->reportable(function (Throwable $e) {
            if ($this->isCritical($e)) {
                Log::channel('daily')->error(' [CRITICAL ERROR] ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                ]);

                // Acionar notificações externas:
                // $this->notifyAdmin($e);
            }
        });

        // Tratamento para erros de integração externa (APIs)
        $this->reportable(function (Throwable $e) {
            if ($this->isExternalApiError($e)) {
                $service = $this->detectExternalService($e);
                
                Log::channel('daily')->warning("[EXTERNAL API ERROR] {$service}: " . $e->getMessage(), [
                    'service' => $service,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        // Tratamento para erros de integração Python/LLM
        $this->reportable(function (Throwable $e) {
            if ($this->isPythonLLMError($e)) {
                Log::channel('daily')->error('[PYTHON/LLM ERROR] ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        });
    }

    /**
     * Renderiza exceções em respostas HTTP apropriadas.
     */
    public function render($request, Throwable $e)
    {
        // Resposta JSON padronizada para APIs
        if ($request->expectsJson()) {
            $status = $this->getStatusCode($e);
            $response = [
                'success' => false,
                'error' => class_basename($e),
                'message' => $e->getMessage(),
            ];

            // Adiciona contexto adicional para erros de agentes
            if ($this->isAgentError($e)) {
                $agent = $this->detectAgent($e);
                $response['agent'] = $agent;
                $response['context'] = $this->getAgentContext($e, $agent);
            }

            // Adiciona contexto para erros de API externa
            if ($this->isExternalApiError($e)) {
                $service = $this->detectExternalService($e);
                $response['service'] = $service;
                $response['type'] = 'external_api_error';
            }

            // Log específico para integração com IA / LLM (Python)
            if ($this->isPythonLLMError($e)) {
                Log::channel('daily')->warning('[API] Erro na integração IA/Python: ' . $e->getMessage());
                $response['type'] = 'llm_error';
            }

            return response()->json($response, $status);
        }

        // Caso não seja JSON, usa o comportamento padrão (ex: views de erro)
        return parent::render($request, $e);
    }

    /**
     * Define o status HTTP com base na exceção.
     */
    protected function getStatusCode(Throwable $e): int
    {
        return match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof AuthenticationException => 401,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof QueryException => 500,
            $e instanceof HttpException => $e->getStatusCode(),
            default => 500,
        };
    }

    /**
     * Define se a exceção é considerada crítica.
     */
    protected function isCritical(Throwable $e): bool
    {
        $critical = [
            QueryException::class,
            HttpException::class,
        ];

        // Erros de agentes que causam falha completa também são críticos
        if ($this->isAgentError($e)) {
            // Erros de banco de dados nos agentes são críticos
            if ($e instanceof QueryException) {
                return true;
            }
            
            // Erros que impedem a execução do agente são críticos
            $criticalMessages = [
                'connection refused',
                'database',
                'table not found',
                'column not found',
            ];
            
            $message = strtolower($e->getMessage());
            foreach ($criticalMessages as $criticalMsg) {
                if (str_contains($message, $criticalMsg)) {
                    return true;
                }
            }
        }

        return in_array(get_class($e), $critical);
    }

    /**
     * Verifica se o erro está relacionado a um dos agentes
     */
    protected function isAgentError(Throwable $e): bool
    {
        $trace = $e->getTraceAsString();
        $message = strtolower($e->getMessage());
        
        $agentKeywords = [
            'agentjuliafetch',
            'agentpedroanalyze',
            'agentkeycompose',
            'agentpublishnotify',
            'agentcleanup',
            'agente júlia',
            'agente pedro',
            'agente key',
            'yahoofinanceservice',
            'newsanalysisservice',
            'llmservice',
        ];

        foreach ($agentKeywords as $keyword) {
            if (str_contains(strtolower($trace), strtolower($keyword)) || 
                str_contains($message, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta qual agente está relacionado ao erro
     */
    protected function detectAgent(Throwable $e): string
    {
        $trace = strtolower($e->getTraceAsString());
        $message = strtolower($e->getMessage());

        if (str_contains($trace, 'agentjuliafetch') || str_contains($message, 'júlia') || str_contains($message, 'julia')) {
            return 'AgentJulia';
        }

        if (str_contains($trace, 'agentpedroanalyze') || str_contains($message, 'pedro')) {
            return 'AgentPedro';
        }

        if (str_contains($trace, 'agentkeycompose') || str_contains($message, 'key')) {
            return 'AgentKey';
        }

        if (str_contains($trace, 'agentpublishnotify') || str_contains($message, 'notify')) {
            return 'AgentPublishNotify';
        }

        if (str_contains($trace, 'agentcleanup') || str_contains($message, 'cleanup')) {
            return 'AgentCleanup';
        }

        return 'UnknownAgent';
    }

    /**
     * Obtém contexto específico do agente para o erro
     */
    protected function getAgentContext(Throwable $e, string $agent): array
    {
        $context = [
            'agent' => $agent,
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Extrai informações adicionais baseado no tipo de erro
        if ($e instanceof QueryException) {
            $context['database_error'] = true;
            $context['sql'] = $e->getSql() ?? null;
        }

        if ($e instanceof HttpException || $e instanceof RequestException) {
            $context['http_status'] = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : null;
        }

        // Tenta extrair símbolo da ação se presente no trace
        if (preg_match('/symbol[:\s]+([A-Z0-9]+)/i', $e->getTraceAsString(), $matches)) {
            $context['symbol'] = $matches[1];
        }

        return $context;
    }

    /**
     * Verifica se o erro é de uma API externa
     */
    protected function isExternalApiError(Throwable $e): bool
    {
        $trace = strtolower($e->getTraceAsString());
        $message = strtolower($e->getMessage());

        $apiKeywords = [
            'yahoo finance',
            'newsapi',
            'news api',
            'http client',
            'request exception',
            'connection timeout',
            'rate limit',
            'api key',
        ];

        foreach ($apiKeywords as $keyword) {
            if (str_contains($trace, $keyword) || str_contains($message, $keyword)) {
                return true;
            }
        }

        return $e instanceof RequestException || 
               (method_exists($e, 'getStatusCode') && $e->getStatusCode() >= 400);
    }

    /**
     * Detecta qual serviço externo está relacionado ao erro
     */
    protected function detectExternalService(Throwable $e): string
    {
        $trace = strtolower($e->getTraceAsString());
        $message = strtolower($e->getMessage());

        if (str_contains($trace, 'yahoofinanceservice') || str_contains($message, 'yahoo')) {
            return 'YahooFinance';
        }

        if (str_contains($trace, 'newsanalysisservice') || str_contains($message, 'newsapi') || str_contains($message, 'news api')) {
            return 'NewsAPI';
        }

        if (str_contains($message, 'rate limit')) {
            return 'RateLimit';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'Timeout';
        }

        return 'ExternalAPI';
    }

    /**
     * Verifica se o erro é relacionado a Python/LLM
     */
    protected function isPythonLLMError(Throwable $e): bool
    {
        $trace = strtolower($e->getTraceAsString());
        $message = strtolower($e->getMessage());
        $file = strtolower($e->getFile());

        $pythonKeywords = [
            'python',
            'llm',
            'run_llm',
            'llmservice',
            'process',
            'python3',
            'python script',
        ];

        foreach ($pythonKeywords as $keyword) {
            if (str_contains($trace, $keyword) || 
                str_contains($message, $keyword) || 
                str_contains($file, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Notifica administradores (e.g., Slack, E-mail, Sentry, etc.)
     * — opcional: adicione sua implementação aqui.
     */
    protected function notifyAdmin(Throwable $e): void
    {
        // Exemplo: enviar para Slack ou serviço externo
        // Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new ExceptionAlert($e));
        
        // Exemplo: enviar email para administradores em erros críticos dos agentes
        // if ($this->isAgentError($e) && $this->isCritical($e)) {
        //     Mail::to(env('ADMIN_EMAIL'))->send(new AgentErrorNotification($e));
        // }
    }
}

