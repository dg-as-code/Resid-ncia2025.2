<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockSymbolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verifica se a tabela já existe antes de criar
        if (Schema::hasTable('stock_symbols')) {
            return;
        }

        Schema::create('stock_symbols', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique()->comment('Símbolo da ação (ex: Petrobras)');
            $table->string('company_name')->comment('Nome da empresa');
            $table->boolean('is_active')->default(true)->comment('Se a ação está sendo monitorada');
            $table->boolean('is_default')->default(false)->comment('Se é uma ação padrão para coleta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_symbols');
    }
}

