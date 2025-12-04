<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Jobs\FetchFinancialDataJob;
use App\Jobs\AnalyzeMarketSentimentJob;
use App\Jobs\DraftArticleJob;
use App\Jobs\NotifyReviewerJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gerenciar análises financeiras via Jobs
 * 
 * FLUXO ALTERNATIVO: Este controller usa Jobs (filas) para executar o fluxo de agentes.
 * 
 * Fluxo Principal (OrchestrationController):
 * - OrchestrationController: Execução síncrona completa (Júlia → Pedro → Key)
 * 
 * Fluxo Alternativo (AnalysisController - via Jobs):
 * - FetchFinancialDataJob: Agente Júlia (coleta dados financeiros)
 * - AnalyzeMarketSentimentJob: Agente Pedro (análise de sentimento)
 * - DraftArticleJob: Agente Key (gera matéria jornalística)
 * - NotifyReviewerJob: Agente PublishNotify (notificação)
 * 
 * NOTA: Para execução síncrona imediata, use OrchestrationController.
 * Para execução assíncrona em fila, use AnalysisController.
 */
class AnalysisController extends Controller
{
    /**
     * Solicita uma nova análise financeira completa
     * 
     * Executa o fluxo completo via Jobs (assíncrono):
     * 1. Agente Júlia: Coleta dados financeiros
     * 2. Agente Pedro: Analisa sentimento do mercado
     * 3. Agente Key: Gera matéria jornalística
     * 4. Agente PublishNotify: Envia notificação
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function requestAnalysis(Request $request): JsonResponse
    {
        // Valida e sanitiza inputs
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'ticker' => 'nullable|string|max:20', // Opcional - será identificado automaticamente
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sanitiza inputs
        $companyName = $this->sanitizeInput($request->input('company_name'));
        $ticker = $request->input('ticker'); // Opcional - será identificado automaticamente pelo Agente Júlia

        try {
            // Cria análise
            // O ticker será identificado automaticamente pelo Agente Júlia a partir do company_name
            $analysis = Analysis::create([
                'company_name' => $companyName,
                'ticker' => $ticker, // Opcional - será preenchido pelo Agente Júlia
                'status' => 'pending',
                'user_id' => auth()->id(),
            ]);

            // Cria cadeia de jobs para execução sequencial
            // Agora passa company_name ao invés de ticker
            Bus::chain([
                new FetchFinancialDataJob($analysis, $companyName),
                new AnalyzeMarketSentimentJob($analysis),
                new DraftArticleJob($analysis),
                new NotifyReviewerJob($analysis),
            ])->dispatch();

            Log::info('AnalysisController: Análise solicitada', [
                'analysis_id' => $analysis->id,
                'company_name' => $companyName,
                'ticker' => $ticker,
            ]);

            return response()->json([
                'message' => 'Análise solicitada com sucesso',
                'analysis' => $analysis,
            ], 201);

        } catch (\Exception $e) {
            Log::error('AnalysisController: Erro ao solicitar análise', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erro ao solicitar análise',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista todas as análises financeiras
     * 
     * Filtros disponíveis:
     * - status: Filtra por status (pending, completed, failed, etc.)
     * - user_id: Filtra por usuário
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Analysis::with(['user', 'stockSymbol', 'article']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $analyses = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($analyses);
    }

    /**
     * Mostra uma análise financeira específica
     * 
     * Inclui todos os relacionamentos:
     * - user: Usuário que solicitou
     * - stockSymbol: Símbolo da ação
     * - financialData: Dados financeiros (Agente Júlia)
     * - sentimentAnalysis: Análise de sentimento (Agente Pedro)
     * - article: Matéria gerada (Agente Key)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $analysis = Analysis::with([
            'user',
            'stockSymbol',
            'financialData',
            'sentimentAnalysis',
            'article',
        ])->findOrFail($id);

        return response()->json($analysis);
    }

    /**
     * Sanitiza input de texto
     */
    protected function sanitizeInput(string $input): string
    {
        // Remove caracteres perigosos
        $input = strip_tags($input);
        $input = trim($input);
        $input = preg_replace('/[<>"\']/', '', $input);
        
        return $input;
    }

    /**
     * Sanitiza ticker
     */
    protected function sanitizeTicker(string $ticker): string
    {
        // Remove caracteres não alfanuméricos (exceto ponto)
        $ticker = preg_replace('/[^A-Z0-9.]/i', '', $ticker);
        $ticker = strtoupper(trim($ticker));
        
        return $ticker;
    }
}

