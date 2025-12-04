<?php

namespace App\Http\Controllers;

use App\Models\StockSymbol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar ações monitoradas (Stock Symbols)
 * 
 * FLUXO DOS AGENTES:
 * - Agente Júlia: Usa StockSymbol para identificar ações e coletar dados financeiros
 * - Agente Pedro: Usa StockSymbol para analisar sentimento de ações específicas
 * - Agente Key: Usa StockSymbol para gerar matérias sobre ações específicas
 * 
 * Relacionamento com o fluxo:
 * - OrchestrationController::getOrCreateStockSymbol() → Cria/busca StockSymbol
 * - YahooFinanceService → Usa StockSymbol para buscar dados
 * - NewsAnalysisService → Usa StockSymbol para filtrar notícias
 * 
 * Campos importantes:
 * - symbol: Símbolo da ação (ex: PETR4, VALE3)
 * - company_name: Nome completo da empresa
 * - is_active: Se a ação está ativa no monitoramento
 * - is_default: Se é uma ação padrão do sistema
 */
class StockSymbolController extends Controller
{
    /**
     * Lista todas as ações monitoradas pelo sistema
     * 
     * Filtros disponíveis:
     * - is_active: Filtra por ações ativas (true/false)
     * - is_default: Filtra por ações padrão (true/false)
     * - search: Busca por símbolo ou nome da empresa
     * 
     * Paginação:
     * - per_page: Itens por página (padrão: 15)
     * 
     * @param Request $request
     * @return JsonResponse
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
     * Exibe uma ação específica com dados relacionados
     * 
     * Inclui relacionamentos (se disponíveis):
     * - latestFinancialData: Últimos dados financeiros (Agente Júlia)
     * - latestSentimentAnalysis: Última análise de sentimento (Agente Pedro)
     * 
     * @param string $id ID do StockSymbol
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $symbol = StockSymbol::findOrFail($id);
        
        // Carrega relacionamentos opcionalmente (se existirem) - Otimizado: seleciona apenas campos necessários
        try {
            $symbol->load([
                'latestFinancialData:id,stock_symbol_id,price,change_percent,collected_at',
                'latestSentimentAnalysis:id,stock_symbol_id,sentiment,sentiment_score,analyzed_at'
            ]);
        } catch (\Exception $e) {
            // Ignora erros de relacionamento (ex: tabelas não existem em testes)
        }

        return response()->json([
            'success' => true,
            'data' => $symbol,
        ]);
    }

    /**
     * Cria uma nova ação para monitoramento
     * 
     * Validações:
     * - symbol: Obrigatório, máximo 10 caracteres, único
     * - company_name: Obrigatório, máximo 255 caracteres
     * - is_active: Boolean (opcional, padrão: true)
     * - is_default: Boolean (opcional, padrão: false)
     * 
     * @param Request $request
     * @return JsonResponse
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
     * Atualiza uma ação existente
     * 
     * Campos opcionais (usar 'sometimes' na validação):
     * - symbol: Máximo 10 caracteres, único (exceto o próprio registro)
     * - company_name: Máximo 255 caracteres
     * - is_active: Boolean
     * - is_default: Boolean
     * 
     * @param Request $request
     * @param string $id ID do StockSymbol
     * @return JsonResponse
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
     * 
     * ATENÇÃO: Esta ação não pode ser desfeita.
     * Verifique se não há dados relacionados (FinancialData, SentimentAnalysis, Articles)
     * antes de remover.
     * 
     * @param string $id ID do StockSymbol
     * @return JsonResponse
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

