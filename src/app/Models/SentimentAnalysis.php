<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model SentimentAnalysis - Análise de Sentimento do Mercado
 * 
 * FLUXO DOS AGENTES:
 * Este model representa análises de sentimento geradas pelo Agente Pedro.
 * 
 * O Agente Pedro:
 * - Busca notícias via NewsAnalysisService
 * - Analisa sentimento com LLM (Gemini)
 * - Gera análise completa incluindo:
 *   - Dados básicos de sentimento (sentiment, sentiment_score, contagens)
 *   - Análise de mercado e macroeconomia (market_analysis, macroeconomic_analysis)
 *   - Métricas de marca e percepção (brand_perception, engagement_metrics, etc.)
 *   - Insights estratégicos (actionable_insights, risk_alerts, etc.)
 *   - Dados digitais e comportamentais (em raw_data._analysis)
 * 
 * Dados são usados pelo Agente Key para gerar matérias jornalísticas.
 * 
 * Estrutura de raw_data:
 * - articles: Array de notícias analisadas
 * - _analysis: Análise enriquecida com:
 *   - digital_data: Volume de menções, sentimento público, engajamento, alcance
 *   - behavioral_data: Intenções de compra, reclamações, feedback, avaliações
 *   - strategic_insights: Insights sobre preço, concorrência, tendências, satisfação
 *   - cost_optimization: Onde cortar custos ou investir
 * 
 * Relacionamentos:
 * - stockSymbol: Ação analisada
 * - articles: Matérias geradas pelo Agente Key usando esta análise
 */
class SentimentAnalysis extends Model
{
    use HasFactory;

    /**
     * Nome da tabela (singular, não plural)
     */
    protected $table = 'sentiment_analysis';

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
        'market_analysis',
        'macroeconomic_analysis',
        'key_insights',
        'recommendation',
        // Métricas de marca
        'total_mentions',
        'mentions_peak',
        'mentions_timeline',
        'sentiment_breakdown',
        'engagement_metrics',
        'engagement_score',
        'investor_confidence',
        'confidence_score',
        'brand_perception',
        'main_themes',
        'emotions_analysis',
        'actionable_insights',
        'improvement_opportunities',
        'risk_alerts',
        'strategic_analysis',
    ];

    protected $casts = [
        'sentiment_score' => 'float',
        'news_sources' => 'array',
        'raw_data' => 'array',
        'market_analysis' => 'array',
        'macroeconomic_analysis' => 'array',
        'key_insights' => 'array',
        'analyzed_at' => 'datetime',
        // Métricas de marca
        'mentions_peak' => 'array',
        'mentions_timeline' => 'array',
        'sentiment_breakdown' => 'array',
        'engagement_metrics' => 'array',
        'engagement_score' => 'float',
        'investor_confidence' => 'array',
        'confidence_score' => 'float',
        'brand_perception' => 'array',
        'main_themes' => 'array',
        'emotions_analysis' => 'array',
        'actionable_insights' => 'array',
        'improvement_opportunities' => 'array',
        'risk_alerts' => 'array',
    ];

    /**
     * Relacionamento com símbolo da ação
     * 
     * @return BelongsTo
     */
    public function stockSymbol(): BelongsTo
    {
        return $this->belongsTo(StockSymbol::class);
    }

    /**
     * Relacionamento com artigos gerados usando esta análise
     * 
     * Artigos são gerados pelo Agente Key usando os dados desta análise.
     * 
     * @return HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Obtém dados digitais de raw_data._analysis.digital_data
     * 
     * @return array|null
     */
    public function getDigitalDataAttribute(): ?array
    {
        $rawData = $this->raw_data ?? [];
        return $rawData['_analysis']['digital_data'] ?? null;
    }

    /**
     * Obtém dados comportamentais de raw_data._analysis.behavioral_data
     * 
     * @return array|null
     */
    public function getBehavioralDataAttribute(): ?array
    {
        $rawData = $this->raw_data ?? [];
        return $rawData['_analysis']['behavioral_data'] ?? null;
    }

    /**
     * Obtém insights estratégicos de raw_data._analysis.strategic_insights
     * 
     * @return array|null
     */
    public function getStrategicInsightsAttribute(): ?array
    {
        $rawData = $this->raw_data ?? [];
        return $rawData['_analysis']['strategic_insights'] ?? null;
    }

    /**
     * Obtém otimização de custos de raw_data._analysis.cost_optimization
     * 
     * @return array|null
     */
    public function getCostOptimizationAttribute(): ?array
    {
        $rawData = $this->raw_data ?? [];
        return $rawData['_analysis']['cost_optimization'] ?? null;
    }

    /**
     * Verifica se a análise tem dados enriquecidos (LLM)
     * 
     * @return bool
     */
    public function hasEnrichedData(): bool
    {
        return !empty($this->market_analysis) 
            || !empty($this->brand_perception) 
            || !empty($this->actionable_insights);
    }

    /**
     * Scope para análises com sentimento positivo
     * 
     * @param $query
     * @return mixed
     */
    public function scopePositive($query)
    {
        return $query->where('sentiment', 'positive');
    }

    /**
     * Scope para análises com sentimento negativo
     * 
     * @param $query
     * @return mixed
     */
    public function scopeNegative($query)
    {
        return $query->where('sentiment', 'negative');
    }

    /**
     * Scope para análises com sentimento neutro
     * 
     * @param $query
     * @return mixed
     */
    public function scopeNeutral($query)
    {
        return $query->where('sentiment', 'neutral');
    }

    /**
     * Scope para análises de um símbolo específico
     * 
     * @param $query
     * @param string $symbol
     * @return mixed
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }
}

