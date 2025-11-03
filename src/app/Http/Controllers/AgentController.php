<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar os agentes de IA via API
 */
class AgentController extends Controller
{
    /**
     * Executa o agente Júlia (coleta dados financeiros)
     */
    public function runJulia(Request $request): JsonResponse
    {
        try {
            $symbol = $request->get('symbol');
            $all = $request->boolean('all', false);

            $command = 'agent:julia:fetch';
            $params = [];

            if ($all) {
                $params['--all'] = true;
            } elseif ($symbol) {
                $params['--symbol'] = $symbol;
            }

            Artisan::call($command, $params);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Agente Júlia executado com sucesso',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Agent Julia execution error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar agente Júlia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa o agente Pedro (análise de sentimento)
     */
    public function runPedro(Request $request): JsonResponse
    {
        try {
            $symbol = $request->get('symbol');
            $all = $request->boolean('all', false);

            $command = 'agent:pedro:analyze';
            $params = [];

            if ($all) {
                $params['--all'] = true;
            } elseif ($symbol) {
                $params['--symbol'] = $symbol;
            }

            Artisan::call($command, $params);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Agente Pedro executado com sucesso',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Agent Pedro execution error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar agente Pedro: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa o agente Key (geração de matéria)
     */
    public function runKey(Request $request): JsonResponse
    {
        try {
            $symbol = $request->get('symbol');
            $force = $request->boolean('force', false);

            $command = 'agent:key:compose';
            $params = [];

            if ($force) {
                $params['--force'] = true;
            }

            if ($symbol) {
                $params['--symbol'] = $symbol;
            }

            Artisan::call($command, $params);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Agente Key executado com sucesso',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Agent Key execution error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar agente Key: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa o agente PublishNotify (notificação)
     */
    public function runPublishNotify(Request $request): JsonResponse
    {
        try {
            $email = $request->get('email');
            $dryRun = $request->boolean('dry-run', false);

            $command = 'agent:publish:notify';
            $params = [];

            if ($dryRun) {
                $params['--dry-run'] = true;
            }

            if ($email) {
                $params['--email'] = $email;
            }

            Artisan::call($command, $params);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Agente PublishNotify executado com sucesso',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Agent PublishNotify execution error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar agente PublishNotify: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa o agente Cleanup (limpeza)
     */
    public function runCleanup(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $dryRun = $request->boolean('dry-run', false);

            $command = 'agent:cleanup';
            $params = [
                '--days' => $days,
            ];

            if ($dryRun) {
                $params['--dry-run'] = true;
            }

            Artisan::call($command, $params);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Agente Cleanup executado com sucesso',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            Log::error('Agent Cleanup execution error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar agente Cleanup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna o status dos agentes
     */
    public function status(): JsonResponse
    {
        $agents = [
            'Julia' => [
                'name' => 'Agente Júlia',
                'description' => 'Coleta dados financeiros de mercado',
                'command' => 'agent:julia:fetch',
                'schedule' => 'A cada 10 minutos',
            ],
            'Pedro' => [
                'name' => 'Agente Pedro',
                'description' => 'Analisa sentimento de mercado e mídia',
                'command' => 'agent:pedro:analyze',
                'schedule' => 'A cada hora',
            ],
            'Key' => [
                'name' => 'Agente Key',
                'description' => 'Gera rascunho de matéria financeira',
                'command' => 'agent:key:compose',
                'schedule' => 'A cada 30 minutos',
            ],
            'PublishNotify' => [
                'name' => 'Agente PublishNotify',
                'description' => 'Notifica revisores sobre matérias pendentes',
                'command' => 'agent:publish:notify',
                'schedule' => 'A cada 15 minutos',
            ],
            'Cleanup' => [
                'name' => 'Agente Cleanup',
                'description' => 'Limpa arquivos temporários e caches',
                'command' => 'agent:cleanup',
                'schedule' => 'Diariamente às 03:00',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }
}

