<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalysesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verifica se a tabela já existe antes de criar
        if (Schema::hasTable('analyses')) {
            return;
        }

        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->comment('Nome da empresa ou ticker');
            $table->string('ticker')->nullable()->comment('Símbolo da ação (ex: Petrobras)');
            $table->enum('status', ['pending', 'fetching_financial_data', 'analyzing_sentiment', 'drafting_article', 'pending_review', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Usuário que solicitou a análise');
            $table->foreignId('stock_symbol_id')->nullable()->constrained('stock_symbols')->onDelete('set null');
            $table->foreignId('financial_data_id')->nullable()->constrained('financial_data')->onDelete('set null');
            $table->foreignId('sentiment_analysis_id')->nullable()->constrained('sentiment_analysis')->onDelete('set null');
            $table->foreignId('article_id')->nullable()->constrained('articles')->onDelete('set null');
            $table->text('error_message')->nullable()->comment('Mensagem de erro se falhar');
            $table->json('metadata')->nullable()->comment('Metadados adicionais');
            $table->timestamp('started_at')->nullable()->comment('Quando começou o processamento');
            $table->timestamp('completed_at')->nullable()->comment('Quando foi concluído');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index('ticker');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analyses');
    }
}

