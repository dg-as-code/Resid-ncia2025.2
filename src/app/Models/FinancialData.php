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
        'price' => 'decimal:4',
        'previous_close' => 'decimal:4',
        'change' => 'decimal:4',
        'change_percent' => 'decimal:4',
        'volume' => 'decimal:0',
        'market_cap' => 'decimal:0',
        'pe_ratio' => 'decimal:4',
        'dividend_yield' => 'decimal:4',
        'high_52w' => 'decimal:4',
        'low_52w' => 'decimal:4',
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

