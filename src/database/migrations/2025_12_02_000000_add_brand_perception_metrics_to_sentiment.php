<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrandPerceptionMetricsToSentiment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sentiment_analysis', function (Blueprint $table) {
            // Volume de Menções
            $table->integer('total_mentions')->nullable()->after('news_count')->comment('Total de menções sobre a marca');
            $table->json('mentions_peak')->nullable()->after('total_mentions')->comment('Pico de menções identificado (JSON: value, date, reason)');
            $table->json('mentions_timeline')->nullable()->after('mentions_peak')->comment('Timeline de menções (JSON)');
            
            // Sentimento Detalhado
            $table->json('sentiment_breakdown')->nullable()->after('mentions_timeline')->comment('Análise detalhada de sentimento (JSON: positive_percentage, negative_percentage, etc)');
            
            // Engajamento
            $table->json('engagement_metrics')->nullable()->after('mentions_timeline')->comment('Métricas de engajamento (cliques, compartilhamentos, etc)');
            $table->decimal('engagement_score', 5, 2)->nullable()->after('engagement_metrics')->comment('Score de engajamento (0-100)');
            
            // Confiança do Investidor/Público
            $table->json('investor_confidence')->nullable()->after('engagement_score')->comment('Indicadores de confiança do investidor');
            $table->decimal('confidence_score', 5, 2)->nullable()->after('investor_confidence')->comment('Score de confiança (0-100)');
            
            // Percepção da Marca
            $table->json('brand_perception')->nullable()->after('confidence_score')->comment('Análise detalhada de percepção da marca');
            $table->json('main_themes')->nullable()->after('brand_perception')->comment('Temas principais identificados');
            $table->json('emotions_analysis')->nullable()->after('main_themes')->comment('Análise de emoções predominantes');
            
            // Análise Estratégica
            $table->json('actionable_insights')->nullable()->after('emotions_analysis')->comment('Insights acionáveis');
            $table->json('improvement_opportunities')->nullable()->after('actionable_insights')->comment('Oportunidades de melhoria');
            $table->json('risk_alerts')->nullable()->after('improvement_opportunities')->comment('Alertas de riscos e tendências');
            $table->text('strategic_analysis')->nullable()->after('risk_alerts')->comment('Análise estratégica completa');
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
                'total_mentions',
                'mentions_peak',
                'mentions_timeline',
                'sentiment_breakdown',
                'engagement_metrics',
                'engagement_score',
                'investor_confidence',
                'confidence_score',
                'brand_perception',
                'main_themes',
                'emotions_analysis',
                'actionable_insights',
                'improvement_opportunities',
                'risk_alerts',
                'strategic_analysis',
            ]);
        });
    }
}

