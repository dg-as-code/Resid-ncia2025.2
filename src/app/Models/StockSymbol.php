<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockSymbol extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'company_name',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Relacionamento com dados financeiros
     */
    public function financialData(): HasMany
    {
        return $this->hasMany(FinancialData::class);
    }

    /**
     * Relacionamento com análises de sentimento
     */
    public function sentimentAnalyses(): HasMany
    {
        return $this->hasMany(SentimentAnalysis::class);
    }

    /**
     * Relacionamento com artigos
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Scope para ações ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ações padrão
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Último dado financeiro coletado
     */
    public function latestFinancialData()
    {
        return $this->hasOne(FinancialData::class)->latestOfMany('collected_at');
    }

    /**
     * Última análise de sentimento
     */
    public function latestSentimentAnalysis()
    {
        return $this->hasOne(SentimentAnalysis::class)->latestOfMany('analyzed_at');
    }
}

