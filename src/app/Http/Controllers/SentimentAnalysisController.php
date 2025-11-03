<?php

namespace App\Http\Controllers;

use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SentimentAnalysisController extends Controller
{
    /**
     * Lista análises de sentimento
     */
    public function index(Request $request): JsonResponse
    {
        $query = SentimentAnalysis::with('stockSymbol');

        // Filtros
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

        // Ordenação
        $orderBy = $request->get('order_by', 'analyzed_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Exibe análise de sentimento específica
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

