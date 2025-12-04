<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Índices para stock_symbols
        if (Schema::hasTable('stock_symbols')) {
            Schema::table('stock_symbols', function (Blueprint $table) {
                if (!$this->hasIndex('stock_symbols', 'idx_symbol')) {
                    $table->index('symbol', 'idx_symbol');
                }
                if (!$this->hasIndex('stock_symbols', 'idx_company_name')) {
                    $table->index('company_name', 'idx_company_name');
                }
                if (!$this->hasIndex('stock_symbols', 'idx_is_active')) {
                    $table->index('is_active', 'idx_is_active');
                }
            });
        }
        
        // Índices para financial_data
        if (Schema::hasTable('financial_data')) {
            Schema::table('financial_data', function (Blueprint $table) {
                if (!$this->hasIndex('financial_data', 'idx_stock_symbol_id')) {
                    $table->index('stock_symbol_id', 'idx_stock_symbol_id');
                }
                if (!$this->hasIndex('financial_data', 'idx_symbol')) {
                    $table->index('symbol', 'idx_symbol');
                }
                if (!$this->hasIndex('financial_data', 'idx_collected_at')) {
                    $table->index('collected_at', 'idx_collected_at');
                }
            });
        }
        
        // Índices para articles
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                if (!$this->hasIndex('articles', 'idx_stock_symbol_id')) {
                    $table->index('stock_symbol_id', 'idx_stock_symbol_id');
                }
                if (!$this->hasIndex('articles', 'idx_status')) {
                    $table->index('status', 'idx_status');
                }
                if (!$this->hasIndex('articles', 'idx_created_at')) {
                    $table->index('created_at', 'idx_created_at');
                }
            });
        }
        
        // Índices para sentiment_analysis
        if (Schema::hasTable('sentiment_analysis')) {
            Schema::table('sentiment_analysis', function (Blueprint $table) {
                if (!$this->hasIndex('sentiment_analysis', 'idx_stock_symbol_id')) {
                    $table->index('stock_symbol_id', 'idx_stock_symbol_id');
                }
                if (!$this->hasIndex('sentiment_analysis', 'idx_symbol')) {
                    $table->index('symbol', 'idx_symbol');
                }
                if (!$this->hasIndex('sentiment_analysis', 'idx_analyzed_at')) {
                    $table->index('analyzed_at', 'idx_analyzed_at');
                }
            });
        }
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('stock_symbols')) {
            Schema::table('stock_symbols', function (Blueprint $table) {
                $table->dropIndex('idx_symbol');
                $table->dropIndex('idx_company_name');
                $table->dropIndex('idx_is_active');
            });
        }
        
        if (Schema::hasTable('financial_data')) {
            Schema::table('financial_data', function (Blueprint $table) {
                $table->dropIndex('idx_stock_symbol_id');
                $table->dropIndex('idx_symbol');
                $table->dropIndex('idx_collected_at');
            });
        }
        
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropIndex('idx_stock_symbol_id');
                $table->dropIndex('idx_status');
                $table->dropIndex('idx_created_at');
            });
        }
        
        if (Schema::hasTable('sentiment_analysis')) {
            Schema::table('sentiment_analysis', function (Blueprint $table) {
                $table->dropIndex('idx_stock_symbol_id');
                $table->dropIndex('idx_symbol');
                $table->dropIndex('idx_analyzed_at');
            });
        }
    }
    
    /**
     * Verifica se um índice já existe usando query SQL direta
     */
    protected function hasIndex($table, $index)
    {
        try {
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$database, $table, $index]
            );
            return isset($result[0]) && $result[0]->count > 0;
        } catch (\Exception $e) {
            // Se não conseguir verificar, assume que não existe
            return false;
        }
    }
}
