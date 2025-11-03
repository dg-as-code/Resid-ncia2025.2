<?php

namespace App\Http\Controllers;

use App\Models\FinancialData;
use App\Models\StockSymbol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinancialDataController extends Controller
{
    /**
     * Lista dados financeiros
     */
    public function index(Request $request): JsonResponse
    {
        $query = FinancialData::with('stockSymbol');

        // Filtros
        if ($request->has('symbol')) {
            $query->where('symbol', $request->get('symbol'));
        }

        if ($request->has('stock_symbol_id')) {
            $query->where('stock_symbol_id', $request->get('stock_symbol_id'));
        }

        if ($request->has('date_from')) {
            $query->where('collected_at', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('collected_at', '<=', $request->get('date_to'));
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'collected_at');
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
     * Exibe dados financeiros específicos
     */
    public function show(string $id): JsonResponse
    {
        $financialData = FinancialData::with('stockSymbol')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $financialData,
        ]);
    }

    /**
     * Retorna os dados financeiros mais recentes de uma ação
     */
    public function latest(string $symbol): JsonResponse
    {
        $stockSymbol = StockSymbol::where('symbol', $symbol)->firstOrFail();
        
        $latestData = FinancialData::where('stock_symbol_id', $stockSymbol->id)
            ->latest('collected_at')
            ->first();

        if (!$latestData) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum dado financeiro encontrado para esta ação',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $latestData,
        ]);
    }
}

