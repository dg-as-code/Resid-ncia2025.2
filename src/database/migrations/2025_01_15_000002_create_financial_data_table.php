<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('financial_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_symbol_id')->constrained('stock_symbols')->onDelete('cascade');
            $table->string('symbol', 10)->index()->comment('Símbolo da ação (duplicado para performance)');
            $table->decimal('price', 15, 4)->nullable()->comment('Preço atual');
            $table->decimal('previous_close', 15, 4)->nullable()->comment('Fechamento anterior');
            $table->decimal('change', 15, 4)->nullable()->comment('Variação');
            $table->decimal('change_percent', 10, 4)->nullable()->comment('Variação percentual');
            $table->decimal('volume', 20, 0)->nullable()->comment('Volume negociado');
            $table->decimal('market_cap', 20, 0)->nullable()->comment('Capitalização de mercado');
            $table->decimal('pe_ratio', 10, 4)->nullable()->comment('P/L');
            $table->decimal('dividend_yield', 10, 4)->nullable()->comment('Dividend yield');
            $table->decimal('high_52w', 15, 4)->nullable()->comment('Máxima 52 semanas');
            $table->decimal('low_52w', 15, 4)->nullable()->comment('Mínima 52 semanas');
            $table->json('raw_data')->nullable()->comment('Dados brutos da API');
            $table->string('source')->default('yahoo_finance')->comment('Fonte dos dados');
            $table->timestamp('collected_at')->useCurrent()->comment('Data de coleta');
            $table->timestamps();

            $table->index(['symbol', 'collected_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_data');
    }
}

