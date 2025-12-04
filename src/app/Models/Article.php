<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_symbol_id',
        'symbol',
        'financial_data_id',
        'sentiment_analysis_id',
        'title',
        'content',
        'status',
        'motivo_reprovacao',
        'recomendacao',
        'metadata',
        'notified_at',
        'reviewed_at',
        'reviewed_by',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'notified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Relacionamento com símbolo
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
     * Relacionamento com revisor
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope para matérias pendentes de revisão
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pendente_revisao');
    }

    /**
     * Scope para matérias não notificadas
     */
    public function scopeNotNotified($query)
    {
        return $query->whereNull('notified_at');
    }

    /**
     * Scope para símbolo específico
     */
    public function scopeForSymbol($query, $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope para matérias aprovadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'aprovado');
    }

    /**
     * Scope para matérias publicadas
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'publicado');
    }
}

