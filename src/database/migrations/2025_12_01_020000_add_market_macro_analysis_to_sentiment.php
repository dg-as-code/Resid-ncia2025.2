<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarketMacroAnalysisToSentiment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sentiment_analysis', function (Blueprint $table) {
            // Análise de mercado
            $table->json('market_analysis')->nullable()->after('raw_data')->comment('Análise de mercado (trend, drivers, riscos, oportunidades)');
            
            // Análise macroeconômica
            $table->json('macroeconomic_analysis')->nullable()->after('market_analysis')->comment('Análise macroeconômica (contexto, setor, indicadores, inflação, juros, câmbio)');
            
            // Insights e recomendações
            $table->json('key_insights')->nullable()->after('macroeconomic_analysis')->comment('Principais insights da análise');
            $table->text('recommendation')->nullable()->after('key_insights')->comment('Recomendação baseada na análise');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sentiment_analysis', function (Blueprint $table) {
            $table->dropColumn([
                'market_analysis',
                'macroeconomic_analysis',
                'key_insights',
                'recommendation',
            ]);
        });
    }
}

