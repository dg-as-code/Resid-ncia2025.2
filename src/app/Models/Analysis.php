<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Analysis - Análises Financeiras Completas
 * 
 * FLUXO DOS AGENTES (via Jobs):
 * Este model representa uma análise financeira completa executada via Jobs (filas).
 * 
 * Fluxo de Status:
 * 1. pending → Análise criada, aguardando início
 * 2. fetching_financial_data → FetchFinancialDataJob (Agente Júlia coletando dados)
 * 3. analyzing_sentiment → AnalyzeMarketSentimentJob (Agente Pedro analisando sentimento)
 * 4. drafting_article → DraftArticleJob (Agente Key gerando matéria)
 * 5. pending_review → NotifyReviewerJob concluído, aguardando revisão humana
 * 6. completed → Análise concluída (artigo aprovado e publicado)
 * 7. failed → Análise falhou em alguma etapa
 * 
 * Relacionamentos:
 * - user: Usuário que solicitou a análise
 * - stockSymbol: Ação monitorada
 * - financialData: Dados financeiros coletados pelo Agente Júlia
 * - sentimentAnalysis: Análise de sentimento gerada pelo Agente Pedro
 * - article: Matéria jornalística gerada pelo Agente Key
 * 
 * NOTA: Para execução síncrona, use OrchestrationController.
 * Para execução assíncrona via Jobs, use AnalysisController.
 */
class Analysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'ticker',
        'status',
        'user_id',
        'stock_symbol_id',
        'financial_data_id',
        'sentiment_analysis_id',
        'article_id',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relacionamento com usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com símbolo da ação
     */
    public function stockSymbol(): BelongsTo
    {
        return $this->belongsTo(StockSymbol::class);
    }

    /**
     * Relacionamento com dados financeiros
     */
    public function financialData(): BelongsTo
    {
        return $this->belongsTo(FinancialData::class);
    }

    /**
     * Relacionamento com análise de sentimento
     */
    public function sentimentAnalysis(): BelongsTo
    {
        return $this->belongsTo(SentimentAnalysis::class);
    }

    /**
     * Relacionamento com artigo
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Scope para análises pendentes (aguardando início)
     * 
     * Status: 'pending'
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para análises em processamento
     * 
     * Inclui qualquer status de processamento:
     * - 'fetching_financial_data' (Agente Júlia)
     * - 'analyzing_sentiment' (Agente Pedro)
     * - 'drafting_article' (Agente Key)
     */
    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['fetching_financial_data', 'analyzing_sentiment', 'drafting_article']);
    }

    /**
     * Scope para análises pendentes de revisão humana
     * 
     * Status: 'pending_review'
     * Artigo gerado pelo Agente Key, aguardando aprovação/reprovação do editor.
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    /**
     * Scope para análises concluídas
     * 
     * Status: 'completed'
     * Artigo foi aprovado e publicado.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para análises falhadas
     * 
     * Status: 'failed'
     * Análise falhou em alguma etapa do fluxo.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Verifica se a análise está completa (todos os agentes concluídos)
     * 
     * @return bool
     */
    public function isComplete(): bool
    {
        return !empty($this->financial_data_id) 
            && !empty($this->sentiment_analysis_id) 
            && !empty($this->article_id);
    }

    /**
     * Verifica se a análise está em processamento
     * 
     * @return bool
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['fetching_financial_data', 'analyzing_sentiment', 'drafting_article']);
    }

    /**
     * Obtém o progresso da análise (0-100%)
     * 
     * @return int
     */
    public function getProgressAttribute(): int
    {
        $steps = 0;
        $total = 4; // Júlia, Pedro, Key, Revisão
        
        if ($this->financial_data_id) $steps++;
        if ($this->sentiment_analysis_id) $steps++;
        if ($this->article_id) $steps++;
        if ($this->status === 'completed') $steps++;
        
        return (int) (($steps / $total) * 100);
    }
}

