<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SentimentAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_symbol_id',
        'symbol',
        'sentiment',
        'sentiment_score',
        'news_count',
        'positive_count',
        'negative_count',
        'neutral_count',
        'trending_topics',
        'news_sources',
        'raw_data',
        'source',
        'analyzed_at',
    ];

    protected $casts = [
        'sentiment_score' => 'decimal:4',
        'news_sources' => 'array',
        'raw_data' => 'array',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Relacionamento com símbolo
     */
    public function stockSymbol(): BelongsTo
    {
        return $this->belongsTo(StockSymbol::class);
    }

    /**
     * Relacionamento com artigos
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}

