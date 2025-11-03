<?php

namespace App\Http\Controllers;

use App\Models\StockSymbol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockSymbolController extends Controller
{
    /**
     * Lista todas as ações monitoradas
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockSymbol::query();

        // Filtros
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->boolean('is_default'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('symbol', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $symbols = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $symbols,
        ]);
    }

    /**
     * Exibe uma ação específica
     */
    public function show(string $id): JsonResponse
    {
        $symbol = StockSymbol::with(['latestFinancialData', 'latestSentimentAnalysis'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $symbol,
        ]);
    }

    /**
     * Cria uma nova ação para monitoramento
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10|unique:stock_symbols,symbol',
            'company_name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $symbol = StockSymbol::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ação criada com sucesso',
            'data' => $symbol,
        ], 201);
    }

    /**
     * Atualiza uma ação
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $symbol = StockSymbol::findOrFail($id);

        $validated = $request->validate([
            'symbol' => 'sometimes|string|max:10|unique:stock_symbols,symbol,' . $id,
            'company_name' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $symbol->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ação atualizada com sucesso',
            'data' => $symbol,
        ]);
    }

    /**
     * Remove uma ação do monitoramento
     */
    public function destroy(string $id): JsonResponse
    {
        $symbol = StockSymbol::findOrFail($id);
        $symbol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ação removida com sucesso',
        ]);
    }
}

