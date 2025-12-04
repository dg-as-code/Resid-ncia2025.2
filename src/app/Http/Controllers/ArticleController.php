<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar artigos/matérias geradas pelo Agente Key
 * 
 * Os artigos são gerados pela redatora veterana (Agente Key) baseados em:
 * - Dados financeiros coletados pelo Agente Júlia
 * - Análise de sentimento e opiniões da mídia do Agente Pedro
 */
class ArticleController extends Controller
{
    /**
     * Lista artigos/matérias geradas pelo Agente Key
     * 
     * Filtros disponíveis:
     * - symbol: Filtrar por símbolo da ação
     * - status: Filtrar por status (pendente_revisao, aprovado, publicado, reprovado)
     * - pending_review: Apenas artigos pendentes de revisão
     * - order_by: Ordenar por (id, title, symbol, status, created_at, updated_at, published_at)
     * - order_dir: Direção (asc, desc)
     * - per_page: Itens por página (1-100)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Article::query();

            // Carrega relacionamentos (com tratamento de erros) - Otimizado: seleciona apenas campos necessários
            try {
                $query->with([
                    'stockSymbol:id,symbol,company_name',
                    'financialData:id,symbol,price,change_percent,stock_symbol_id',
                    'sentimentAnalysis:id,sentiment,sentiment_score,stock_symbol_id',
                    'reviewer:id,name,email'
                ])->select('id', 'title', 'symbol', 'status', 'created_at', 'updated_at', 'stock_symbol_id', 'financial_data_id', 'sentiment_analysis_id', 'reviewed_by', 'published_at');
            } catch (\Exception $e) {
                // Se algum relacionamento falhar, continua sem ele
                Log::warning('ArticleController: Erro ao carregar relacionamentos', [
                    'error' => $e->getMessage()
                ]);
            }

            // Filtros
            if ($request->has('symbol')) {
                $query->where('symbol', $request->get('symbol'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('stock_symbol_id')) {
                $query->where('stock_symbol_id', $request->get('stock_symbol_id'));
            }

            // Filtro por status específico
            if ($request->has('pending_review')) {
                $query->pendingReview();
            }

            // Ordenação - valida coluna e direção
            $orderBy = $request->get('order_by', 'created_at');
            $allowedOrderBy = ['id', 'title', 'symbol', 'status', 'created_at', 'updated_at', 'published_at'];
            if (!in_array($orderBy, $allowedOrderBy)) {
                $orderBy = 'created_at';
            }
            
            $orderDir = strtolower($request->get('order_dir', 'desc'));
            if (!in_array($orderDir, ['asc', 'desc'])) {
                $orderDir = 'desc';
            }
            
            $query->orderBy($orderBy, $orderDir);

            // Paginação - valida per_page
            $perPage = (int) $request->get('per_page', 15);
            if ($perPage < 1 || $perPage > 100) {
                $perPage = 15;
            }
            
            $articles = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $articles,
            ]);
        } catch (\Exception $e) {
            Log::error('ArticleController: Erro ao listar artigos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar artigos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe um artigo específico gerado pelo Agente Key
     * 
     * Retorna o artigo completo com:
     * - Dados financeiros relacionados (Agente Júlia)
     * - Análise de sentimento relacionada (Agente Pedro)
     * - Conteúdo HTML formatado
     */
    public function show(string $id): JsonResponse
    {
        $article = Article::with(['stockSymbol', 'financialData', 'sentimentAnalysis', 'reviewer'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }

    /**
     * Aprova um artigo para publicação
     * 
     * Aprova a matéria gerada pelo Agente Key após revisão humana.
     * O artigo passa de 'pendente_revisao' para 'aprovado' e depois 'publicado'.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        if ($article->status !== 'pendente_revisao') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas artigos com status "pendente_revisao" podem ser aprovados',
            ], 422);
        }

        $article->update([
            'status' => 'aprovado',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        Log::info('Article approved', [
            'article_id' => $article->id,
            'symbol' => $article->symbol,
            'reviewed_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artigo aprovado com sucesso',
            'data' => $article->fresh(),
        ]);
    }

    /**
     * Reprova um artigo
     * 
     * Reprova a matéria gerada pelo Agente Key.
     * Requer motivo da reprovação para feedback ao sistema.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'motivo_reprovacao' => 'required|string|max:1000',
        ]);

        $article = Article::findOrFail($id);

        if ($article->status !== 'pendente_revisao') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas artigos com status "pendente_revisao" podem ser reprovados',
            ], 422);
        }

        $article->update([
            'status' => 'reprovado',
            'motivo_reprovacao' => $validated['motivo_reprovacao'],
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        Log::info('Article rejected', [
            'article_id' => $article->id,
            'symbol' => $article->symbol,
            'reviewed_by' => auth()->id(),
            'reason' => $validated['motivo_reprovacao'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artigo reprovado com sucesso',
            'data' => $article->fresh(),
        ]);
    }

    /**
     * Publica um artigo aprovado
     */
    public function publish(string $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        if ($article->status !== 'aprovado') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas artigos aprovados podem ser publicados',
            ], 422);
        }

        $article->update([
            'status' => 'publicado',
            'published_at' => now(),
        ]);

        Log::info('Article published', [
            'article_id' => $article->id,
            'symbol' => $article->symbol,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artigo publicado com sucesso',
            'data' => $article->fresh(),
        ]);
    }

    /**
     * Remove um artigo
     */
    public function destroy(string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $article->delete();

        Log::info('Article deleted', [
            'article_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artigo removido com sucesso',
        ]);
    }
}

