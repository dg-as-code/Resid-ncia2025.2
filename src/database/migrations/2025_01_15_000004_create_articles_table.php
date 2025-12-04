<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verifica se a tabela já existe antes de criar
        if (Schema::hasTable('articles')) {
            // Se a tabela já existe, apenas verifica/adiciona a coluna reviewed_by se necessário
            if (!Schema::hasColumn('articles', 'reviewed_by')) {
                Schema::table('articles', function (Blueprint $table) {
                    $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->onDelete('set null');
                });
            } else {
                // Tenta adicionar a foreign key se não existir (pode falhar silenciosamente se já existir)
                try {
                    Schema::table('articles', function (Blueprint $table) {
                        $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                    });
                } catch (\Exception $e) {
                    // Foreign key já existe ou erro ao criar, ignora
                }
            }
            return;
        }

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_symbol_id')->constrained('stock_symbols')->onDelete('cascade');
            $table->string('symbol', 10)->index()->comment('Símbolo da ação');
            $table->foreignId('financial_data_id')->nullable()->constrained('financial_data')->onDelete('set null');
            $table->foreignId('sentiment_analysis_id')->nullable()->constrained('sentiment_analysis')->onDelete('set null');
            $table->string('title')->comment('Título da matéria');
            $table->text('content')->comment('Conteúdo da matéria');
            $table->enum('status', ['pendente_revisao', 'aprovado', 'reprovado', 'publicado'])->default('pendente_revisao');
            $table->text('motivo_reprovacao')->nullable();
            $table->text('recomendacao')->nullable()->comment('Recomendação de compra/venda');
            $table->json('metadata')->nullable()->comment('Metadados adicionais (fontes, datas, etc)');
            $table->timestamp('notified_at')->nullable()->comment('Quando foi notificado para revisão');
            $table->timestamp('reviewed_at')->nullable()->comment('Quando foi revisado');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('published_at')->nullable()->comment('Quando foi publicado');
            $table->timestamps();

            $table->index(['symbol', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}

