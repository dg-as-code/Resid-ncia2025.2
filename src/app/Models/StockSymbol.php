<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model StockSymbol - Ações Monitoradas
 * 
 * FLUXO DOS AGENTES:
 * StockSymbol é usado por todos os agentes no fluxo:
 * 
 * - Agente Júlia: Usa StockSymbol para identificar ações e coletar dados financeiros
 *   - Cria/busca StockSymbol via getOrCreateStockSymbol()
 *   - Salva FinancialData associado ao StockSymbol
 * 
 * - Agente Pedro: Usa StockSymbol para analisar sentimento de ações específicas
 *   - Busca notícias relacionadas ao símbolo
 *   - Salva SentimentAnalysis associado ao StockSymbol
 * 
 * - Agente Key: Usa StockSymbol para gerar matérias sobre ações específicas
 *   - Usa dados financeiros e de sentimento do StockSymbol
 *   - Gera Article associado ao StockSymbol
 * 
 * Campos importantes:
 * - symbol: Símbolo da ação (ex: PETR4, VALE3)
 * - company_name: Nome completo da empresa
 * - is_active: Se a ação está ativa no monitoramento (agentes processam apenas ações ativas)
 * - is_default: Se é uma ação padrão do sistema (aparece em listas padrão)
 * 
 * Relacionamentos:
 * - financialData: Dados financeiros coletados pelo Agente Júlia
 * - sentimentAnalyses: Análises de sentimento geradas pelo Agente Pedro
 * - articles: Matérias geradas pelo Agente Key
 * - latestFinancialData: Último dado financeiro (relacionamento otimizado)
 * - latestSentimentAnalysis: Última análise de sentimento (relacionamento otimizado)
 */
class StockSymbol extends Model
{
    use HasFactory;

    /**
     * Nome da tabela (explicitamente definido para evitar confusão)
     */
    protected $table = 'stock_symbols';

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
     * Relacionamento com dados financeiros coletados pelo Agente Júlia
     * 
     * @return HasMany
     */
    public function financialData(): HasMany
    {
        return $this->hasMany(FinancialData::class);
    }

    /**
     * Relacionamento com análises de sentimento geradas pelo Agente Pedro
     * 
     * @return HasMany
     */
    public function sentimentAnalyses(): HasMany
    {
        return $this->hasMany(SentimentAnalysis::class);
    }

    /**
     * Relacionamento com artigos gerados pelo Agente Key
     * 
     * @return HasMany
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Scope para ações ativas no monitoramento
     * 
     * Apenas ações ativas são processadas pelos agentes.
     * 
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ações padrão do sistema
     * 
     * Ações padrão aparecem em listas padrão e são monitoradas automaticamente.
     * 
     * @param $query
     * @return mixed
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Último dado financeiro coletado pelo Agente Júlia
     * 
     * Relacionamento otimizado (hasOne latestOfMany) para obter apenas o mais recente.
     * 
     * @return HasOne
     */
    public function latestFinancialData(): HasOne
    {
        return $this->hasOne(FinancialData::class)->latestOfMany('collected_at');
    }

    /**
     * Última análise de sentimento gerada pelo Agente Pedro
     * 
     * Relacionamento otimizado (hasOne latestOfMany) para obter apenas a mais recente.
     * 
     * @return HasOne
     */
    public function latestSentimentAnalysis(): HasOne
    {
        return $this->hasOne(SentimentAnalysis::class)->latestOfMany('analyzed_at');
    }

    /**
     * Verifica se a ação tem dados financeiros recentes
     * 
     * @param int $days Número de dias para considerar "recente" (padrão: 1)
     * @return bool
     */
    public function hasRecentFinancialData(int $days = 1): bool
    {
        $latest = $this->latestFinancialData;
        if (!$latest) {
            return false;
        }
        
        return $latest->collected_at && $latest->collected_at->isAfter(now()->subDays($days));
    }

    /**
     * Verifica se a ação tem análise de sentimento recente
     * 
     * @param int $days Número de dias para considerar "recente" (padrão: 1)
     * @return bool
     */
    public function hasRecentSentimentAnalysis(int $days = 1): bool
    {
        $latest = $this->latestSentimentAnalysis;
        if (!$latest) {
            return false;
        }
        
        return $latest->analyzed_at && $latest->analyzed_at->isAfter(now()->subDays($days));
    }

    /**
     * Scope para ações com dados financeiros recentes
     * 
     * @param $query
     * @param int $days
     * @return mixed
     */
    public function scopeWithRecentFinancialData($query, int $days = 1)
    {
        return $query->whereHas('financialData', function ($q) use ($days) {
            $q->where('collected_at', '>=', now()->subDays($days));
        });
    }

    /**
     * Scope para ações com análise de sentimento recente
     * 
     * @param $query
     * @param int $days
     * @return mixed
     */
    public function scopeWithRecentSentimentAnalysis($query, int $days = 1)
    {
        return $query->whereHas('sentimentAnalyses', function ($q) use ($days) {
            $q->where('analyzed_at', '>=', now()->subDays($days));
        });
    }
}

