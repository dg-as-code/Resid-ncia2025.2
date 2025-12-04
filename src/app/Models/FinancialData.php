<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialData extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_symbol_id',
        'symbol',
        'price',
        'previous_close',
        'change',
        'change_percent',
        'volume',
        'market_cap',
        'pe_ratio',
        'dividend_yield',
        'high_52w',
        'low_52w',
        'raw_data',
        'source',
        'collected_at',
    ];

    protected $casts = [
        'price' => 'float',
        'previous_close' => 'float',
        'change' => 'float',
        'change_percent' => 'float',
        'volume' => 'integer',
        'market_cap' => 'integer',
        'pe_ratio' => 'float',
        'dividend_yield' => 'float',
        'high_52w' => 'float',
        'low_52w' => 'float',
        'raw_data' => 'array',
        'collected_at' => 'datetime',
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

