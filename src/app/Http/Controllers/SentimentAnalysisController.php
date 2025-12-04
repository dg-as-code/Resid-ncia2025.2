<?php

namespace App\Http\Controllers;

use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar análises de sentimento do mercado
 * 
 * FLUXO DOS AGENTES:
 * - Agente Pedro: Gera análises de sentimento baseadas em notícias e mídia
 * - Dados são coletados pelo NewsAnalysisService e processados pelo AgentPedroAnalyze
 * - Análises incluem: sentimento básico, dados digitais, comportamentais, insights estratégicos
 * 
 * Relacionamento com o fluxo:
 * - OrchestrationController::executePedro() → Cria SentimentAnalysis
 * - AgentPedroAnalyze (Command) → Também cria SentimentAnalysis
 * - Agente Key usa estes dados para gerar matérias jornalísticas
 */
class SentimentAnalysisController extends Controller
{
    /**
     * Lista análises de sentimento do mercado
     * 
     * Filtros disponíveis:
     * - symbol: Filtra por símbolo da ação (ex: PETR4)
     * - stock_symbol_id: Filtra por ID do StockSymbol
     * - sentiment: Filtra por sentimento (positive, negative, neutral)
     * - date_from: Data inicial (formato: Y-m-d)
     * - date_to: Data final (formato: Y-m-d)
     * 
     * Ordenação:
     * - order_by: analyzed_at, sentiment_score, news_count, created_at
     * - order_dir: asc, desc
     * 
     * Paginação:
     * - per_page: Itens por página (máximo 100)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Otimizado: eager loading e índices para melhor performance
        $query = SentimentAnalysis::with('stockSymbol');

        // Filtros (otimizado: usa where quando possível)
        if ($request->has('symbol')) {
            $query->where('symbol', $request->get('symbol'));
        }

        if ($request->has('stock_symbol_id')) {
            $query->where('stock_symbol_id', $request->get('stock_symbol_id'));
        }

        if ($request->has('sentiment')) {
            $query->where('sentiment', $request->get('sentiment'));
        }

        if ($request->has('date_from')) {
            $query->where('analyzed_at', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('analyzed_at', '<=', $request->get('date_to'));
        }

        // Ordenação (otimizado: valida coluna para evitar SQL injection)
        $orderBy = $request->get('order_by', 'analyzed_at');
        $allowedOrderBy = ['analyzed_at', 'sentiment_score', 'news_count', 'created_at'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'analyzed_at';
        }
        
        $orderDir = strtolower($request->get('order_dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc']) ? $orderDir : 'desc';
        
        $query->orderBy($orderBy, $orderDir);

        // Paginação (otimizado: limita máximo de itens por página)
        $perPage = min((int)$request->get('per_page', 15), 100); // Máximo 100 itens
        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Exibe uma análise de sentimento específica
     * 
     * Inclui relacionamento com StockSymbol.
     * 
     * @param string $id ID da análise de sentimento
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $analysis = SentimentAnalysis::with('stockSymbol')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Retorna a análise de sentimento mais recente de uma ação
     * 
     * Útil para obter a última análise do Agente Pedro para um símbolo específico.
     * 
     * @param string $symbol Símbolo da ação (ex: PETR4, VALE3)
     * @return JsonResponse
     */
    public function latest(string $symbol): JsonResponse
    {
        $stockSymbol = StockSymbol::where('symbol', $symbol)->firstOrFail();
        
        $latestAnalysis = SentimentAnalysis::where('stock_symbol_id', $stockSymbol->id)
            ->latest('analyzed_at')
            ->first();

        if (!$latestAnalysis) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma análise de sentimento encontrada para esta ação',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $latestAnalysis,
        ]);
    }
}

