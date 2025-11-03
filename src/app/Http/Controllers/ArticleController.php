<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    /**
     * Lista artigos/matérias
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::with(['stockSymbol', 'financialData', 'sentimentAnalysis', 'reviewer']);

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

        // Ordenação
        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $articles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    /**
     * Exibe um artigo específico
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
     * Aprova um artigo
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

