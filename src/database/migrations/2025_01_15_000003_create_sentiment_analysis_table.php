<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSentimentAnalysisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sentiment_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_symbol_id')->constrained('stock_symbols')->onDelete('cascade');
            $table->string('symbol', 10)->index()->comment('Símbolo da ação (duplicado para performance)');
            $table->enum('sentiment', ['positive', 'negative', 'neutral'])->default('neutral');
            $table->decimal('sentiment_score', 5, 4)->default(0)->comment('Score de sentimento (-1 a 1)');
            $table->integer('news_count')->default(0)->comment('Número de notícias analisadas');
            $table->integer('positive_count')->default(0);
            $table->integer('negative_count')->default(0);
            $table->integer('neutral_count')->default(0);
            $table->text('trending_topics')->nullable()->comment('Tópicos em alta (JSON)');
            $table->json('news_sources')->nullable()->comment('Fontes de notícias analisadas');
            $table->json('raw_data')->nullable()->comment('Dados brutos da análise');
            $table->string('source')->default('news_api')->comment('Fonte da análise');
            $table->timestamp('analyzed_at')->useCurrent()->comment('Data de análise');
            $table->timestamps();

            $table->index(['symbol', 'analyzed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sentiment_analysis');
    }
}

