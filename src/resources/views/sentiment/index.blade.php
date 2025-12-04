@extends('layouts.app')

@section('title', 'Análise de Sentimento e Percepção de Marca')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Análise de Sentimento e Percepção de Marca</h1>
            <p class="text-gray-400 mt-1">Coletadas pelo Agente Pedro - Análise Profunda de Métricas</p>
        </div>
        <input type="text" id="symbolFilter" onkeyup="loadSentimentAnalysis()" placeholder="Filtrar por ticker..." class="bg-gray-800 border border-gray-600 rounded px-4 py-2 text-white">
    </div>

    <div id="sentiment-container" class="space-y-4">
        <p class="text-gray-500 text-center py-8">Carregando análises de sentimento...</p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', loadSentimentAnalysis);

    async function loadSentimentAnalysis() {
        const symbol = document.getElementById('symbolFilter').value;
        const container = document.getElementById('sentiment-container');
        
        container.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando...</p>';
        
        try {
            let url = `${window.API_URL}/sentiment-analysis?per_page=20&order_by=analyzed_at&order_dir=desc`;
            if (symbol) url += `&symbol=${symbol}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data) {
                const analyses = Array.isArray(data.data.data) ? data.data.data : data.data;
                
                if (analyses.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhuma análise encontrada</p>';
                    return;
                }
                
                container.innerHTML = analyses.map(analysis => {
                    // Funções auxiliares para garantir tipos corretos
                    function ensureObject(value, defaultValue = {}) {
                        if (value === null || value === undefined) return defaultValue;
                        if (typeof value === 'string') {
                            try {
                                return JSON.parse(value);
                            } catch (e) {
                                console.warn('Erro ao fazer parse JSON:', e, value);
                                return defaultValue;
                            }
                        }
                        if (typeof value === 'object' && !Array.isArray(value)) return value;
                        return defaultValue;
                    }

                    function ensureArray(value, defaultValue = []) {
                        if (value === null || value === undefined) return defaultValue;
                        if (typeof value === 'string') {
                            try {
                                const parsed = JSON.parse(value);
                                return Array.isArray(parsed) ? parsed : defaultValue;
                            } catch (e) {
                                console.warn('Erro ao fazer parse JSON array:', e, value);
                                return defaultValue;
                            }
                        }
                        if (Array.isArray(value)) return value;
                        return defaultValue;
                    }

                    // Parse JSON strings se necessário (com fallback seguro)
                    const mentionsPeak = ensureObject(analysis.mentions_peak, {});
                    const sentimentBreakdown = ensureObject(analysis.sentiment_breakdown, {});
                    const mainThemes = ensureArray(analysis.main_themes, []);
                    const engagementMetrics = ensureObject(analysis.engagement_metrics, {});
                    const investorConfidence = ensureObject(analysis.investor_confidence, {});
                    const brandPerception = ensureObject(analysis.brand_perception, {});
                    const actionableInsights = ensureArray(analysis.actionable_insights, []);
                    const improvementOpportunities = ensureArray(analysis.improvement_opportunities, []);
                    const riskAlerts = ensureArray(analysis.risk_alerts, []);
                    
                    // Extrai novos dados de raw_data (com tratamento robusto)
                    const rawData = ensureObject(analysis.raw_data, {});
                    
                    // Extrai dados digitais, comportamentais, insights estratégicos e otimização de custos
                    // Estrutura normalizada: { articles: [], _analysis: { digital_data, behavioral_data, ... } }
                    // Suporta também estruturas antigas para compatibilidade
                    const digitalData = rawData._analysis?.digital_data 
                        || rawData.digital_data 
                        || {};
                    const behavioralData = rawData._analysis?.behavioral_data 
                        || rawData.behavioral_data 
                        || {};
                    const strategicInsights = ensureArray(
                        rawData._analysis?.strategic_insights 
                        || rawData.strategic_insights,
                        []
                    );
                    const costOptimization = rawData._analysis?.cost_optimization 
                        || rawData.cost_optimization 
                        || {};
                    
                    return `
                        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700 space-y-6">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold">${analysis.symbol}</h3>
                                    <p class="text-sm text-gray-400 mt-1">
                                        Analisado: ${new Date(analysis.analyzed_at).toLocaleString('pt-BR')}
                                    </p>
                                </div>
                                <span class="px-4 py-2 rounded text-sm font-bold ${getSentimentClass(analysis.sentiment)}">
                                    ${analysis.sentiment || 'N/A'}
                                </span>
                            </div>

                            <!-- Métricas Básicas -->
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div class="bg-gray-900 p-3 rounded">
                                    <p class="text-gray-400">Score Sentimento</p>
                                    <p class="font-semibold text-lg">${parseFloat(analysis.sentiment_score || 0).toFixed(2)}</p>
                                </div>
                                <div class="bg-gray-900 p-3 rounded">
                                    <p class="text-gray-400">Total Menções</p>
                                    <p class="font-semibold text-lg">${analysis.total_mentions || analysis.news_count || 0}</p>
                                </div>
                                <div class="bg-gray-900 p-3 rounded">
                                    <p class="text-gray-400">Engajamento</p>
                                    <p class="font-semibold text-lg">${parseFloat(analysis.engagement_score || 0).toFixed(0)}%</p>
                                </div>
                                <div class="bg-gray-900 p-3 rounded">
                                    <p class="text-gray-400">Confiança</p>
                                    <p class="font-semibold text-lg">${parseFloat(analysis.confidence_score || investorConfidence.overall_confidence_score || 0).toFixed(0)}%</p>
                                </div>
                            </div>

                            <!-- Volume de Menções -->
                            ${mentionsPeak.value ? `
                                <div class="bg-gray-900 p-4 rounded">
                                    <h4 class="font-semibold mb-2 text-blue-400">📊 Volume de Menções</h4>
                                    <p class="text-sm text-gray-300">
                                        <strong>Pico:</strong> ${mentionsPeak.value} menções em ${mentionsPeak.date || 'N/A'}<br>
                                        <strong>Razão:</strong> ${mentionsPeak.reason || 'Não especificado'}
                                    </p>
                                </div>
                            ` : ''}

                            <!-- Sentimento Detalhado -->
                            ${sentimentBreakdown.positive_percentage !== undefined ? `
                                <div class="bg-gray-900 p-4 rounded">
                                    <h4 class="font-semibold mb-2 text-green-400">😊 Análise de Sentimento</h4>
                                    <div class="grid grid-cols-3 gap-2 mb-2">
                                        <div>
                                            <p class="text-xs text-gray-400">Positivo</p>
                                            <p class="font-semibold text-green-400">${parseFloat(sentimentBreakdown.positive_percentage || 0).toFixed(1)}%</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">Negativo</p>
                                            <p class="font-semibold text-red-400">${parseFloat(sentimentBreakdown.negative_percentage || 0).toFixed(1)}%</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">Neutro</p>
                                            <p class="font-semibold text-yellow-400">${parseFloat(sentimentBreakdown.neutral_percentage || 0).toFixed(1)}%</p>
                                        </div>
                                    </div>
                                    ${sentimentBreakdown.dominant_emotions ? `
                                        <p class="text-xs text-gray-400 mt-2">
                                            <strong>Emoções predominantes:</strong> ${Array.isArray(sentimentBreakdown.dominant_emotions) ? sentimentBreakdown.dominant_emotions.join(', ') : sentimentBreakdown.dominant_emotions}
                                        </p>
                                    ` : ''}
                                    ${sentimentBreakdown.sentiment_balance ? `
                                        <p class="text-xs text-gray-300 mt-2">${sentimentBreakdown.sentiment_balance}</p>
                                    ` : ''}
                                </div>
                            ` : ''}

                            <!-- Temas Principais -->
                            ${mainThemes.length > 0 ? `
                                <div class="bg-gray-900 p-4 rounded">
                                    <h4 class="font-semibold mb-2 text-purple-400">🎯 Temas Principais</h4>
                                    <div class="space-y-2">
                                        ${mainThemes.map(theme => `
                                            <div class="flex justify-between items-start text-sm">
                                                <div class="flex-1">
                                                    <p class="font-semibold">${theme.theme || 'N/A'}</p>
                                                    <p class="text-xs text-gray-400">${theme.explanation || ''}</p>
                                                </div>
                                                <div class="text-right ml-4">
                                                    <span class="px-2 py-1 rounded text-xs ${getImpactClass(theme.impact)}">${theme.impact || 'N/A'}</span>
                                                    <p class="text-xs text-gray-400 mt-1">${theme.frequency || 0} menções</p>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Engajamento -->
                            ${engagementMetrics.engagement_score !== undefined ? `
                                <div class="bg-gray-900 p-4 rounded">
                                    <h4 class="font-semibold mb-2 text-yellow-400">📈 Métricas de Engajamento</h4>
                                    <div class="grid grid-cols-3 gap-2 mb-2">
                                        <div>
                                            <p class="text-xs text-gray-400">Score Engajamento</p>
                                            <p class="font-semibold">${parseFloat(engagementMetrics.engagement_score || 0).toFixed(0)}%</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">Relevância</p>
                                            <p class="font-semibold">${parseFloat(engagementMetrics.relevance_score || 0).toFixed(0)}%</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">Confiança</p>
                                            <p class="font-semibold">${parseFloat(engagementMetrics.trust_score || 0).toFixed(0)}%</p>
                                        </div>
                                    </div>
                                    ${engagementMetrics.interpretation ? `
                                        <p class="text-xs text-gray-300 mt-2">${engagementMetrics.interpretation}</p>
                                    ` : ''}
                                </div>
                            ` : ''}

                            <!-- Confiança do Investidor -->
                            ${investorConfidence.overall_confidence_score !== undefined ? `
                                <div class="bg-gray-900 p-4 rounded">
                                    <h4 class="font-semibold mb-2 text-indigo-400">💼 Confiança do Investidor/Público</h4>
                                    <p class="text-sm font-semibold mb-2">Score: ${parseFloat(investorConfidence.overall_confidence_score || 0).toFixed(0)}%</p>
                                    ${investorConfidence.interpretation ? `
                                        <p class="text-xs text-gray-300">${investorConfidence.interpretation}</p>
                                    ` : ''}
                                </div>
                            ` : ''}

                            <!-- Insights Acionáveis -->
                            ${actionableInsights.length > 0 ? `
                                <div class="bg-blue-900 bg-opacity-30 p-4 rounded border border-blue-500">
                                    <h4 class="font-semibold mb-2 text-blue-300">💡 Insights Acionáveis</h4>
                                    <div class="space-y-2">
                                        ${actionableInsights.map(insight => `
                                            <div class="text-sm">
                                                <p class="font-semibold">${insight.insight || 'N/A'}</p>
                                                <p class="text-xs text-gray-300">Prioridade: <span class="${getPriorityClass(insight.priority)}">${insight.priority || 'N/A'}</span> | Ação: ${insight.action || 'N/A'}</p>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Oportunidades de Melhoria -->
                            ${improvementOpportunities.length > 0 ? `
                                <div class="bg-green-900 bg-opacity-30 p-4 rounded border border-green-500">
                                    <h4 class="font-semibold mb-2 text-green-300">🚀 Oportunidades de Melhoria</h4>
                                    <div class="space-y-2">
                                        ${improvementOpportunities.map(opp => `
                                            <div class="text-sm">
                                                <p class="font-semibold">${opp.opportunity || 'N/A'}</p>
                                                <p class="text-xs text-gray-300">
                                                    Impacto: ${opp.impact || 'N/A'} | 
                                                    Viabilidade: ${opp.feasibility || 'N/A'}
                                                </p>
                                                <p class="text-xs text-gray-400 mt-1">${opp.recommendation || ''}</p>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Alertas de Risco -->
                            ${riskAlerts.length > 0 ? `
                                <div class="bg-red-900 bg-opacity-30 p-4 rounded border border-red-500">
                                    <h4 class="font-semibold mb-2 text-red-300">⚠️ Alertas de Risco e Tendências</h4>
                                    <div class="space-y-2">
                                        ${riskAlerts.map(risk => `
                                            <div class="text-sm">
                                                <p class="font-semibold">${risk.risk || 'N/A'}</p>
                                                <p class="text-xs text-gray-300">
                                                    Severidade: <span class="${getSeverityClass(risk.severity)}">${risk.severity || 'N/A'}</span> | 
                                                    Tendência: ${risk.trend || 'N/A'}
                                                </p>
                                                <p class="text-xs text-gray-400 mt-1">${risk.recommendation || ''}</p>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Análise Estratégica -->
                            ${analysis.strategic_analysis ? `
                                <div class="bg-gray-900 p-4 rounded border-l-4 border-blue-500">
                                    <h4 class="font-semibold mb-2 text-blue-400">📋 Análise Estratégica Completa</h4>
                                    <p class="text-sm text-gray-300 whitespace-pre-line">${analysis.strategic_analysis}</p>
                                </div>
                            ` : ''}

                            <!-- Dados Digitais -->
                            ${digitalData.volume_mentions || digitalData.sentiment_public || digitalData.engagement || digitalData.reach ? `
                                <div class="bg-gray-900 p-4 rounded border-l-4 border-cyan-500">
                                    <h4 class="font-semibold mb-3 text-cyan-400">💻 Dados Digitais</h4>
                                    
                                    ${digitalData.volume_mentions ? `
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-400 mb-1">Volume de Menções</p>
                                            <p class="text-sm font-semibold">Total: ${digitalData.volume_mentions.total || 0}</p>
                                            <p class="text-xs text-gray-300">Relevância: ${digitalData.volume_mentions.relevance || 'N/A'}</p>
                                            ${digitalData.volume_mentions.notoriety ? `<p class="text-xs text-gray-400 mt-1">${digitalData.volume_mentions.notoriety}</p>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${digitalData.sentiment_public ? `
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-400 mb-1">Sentimento Público</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                <div>
                                                    <p class="text-xs text-gray-400">Positivo</p>
                                                    <p class="text-sm font-semibold text-green-400">${parseFloat(digitalData.sentiment_public.positive || 0).toFixed(1)}%</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Negativo</p>
                                                    <p class="text-sm font-semibold text-red-400">${parseFloat(digitalData.sentiment_public.negative || 0).toFixed(1)}%</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Neutro</p>
                                                    <p class="text-sm font-semibold text-yellow-400">${parseFloat(digitalData.sentiment_public.neutral || 0).toFixed(1)}%</p>
                                                </div>
                                            </div>
                                            ${digitalData.sentiment_public.interpretation ? `<p class="text-xs text-gray-300 mt-2">${digitalData.sentiment_public.interpretation}</p>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${digitalData.engagement ? `
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-400 mb-1">Engajamento</p>
                                            <div class="grid grid-cols-2 gap-2 mb-2">
                                                <div>
                                                    <p class="text-xs text-gray-400">Cliques</p>
                                                    <p class="text-sm font-semibold">${(digitalData.engagement.clicks_estimated || 0).toLocaleString('pt-BR')}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Compartilhamentos</p>
                                                    <p class="text-sm font-semibold">${(digitalData.engagement.shares_estimated || 0).toLocaleString('pt-BR')}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Comentários</p>
                                                    <p class="text-sm font-semibold">${(digitalData.engagement.comments_estimated || 0).toLocaleString('pt-BR')}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Permanência</p>
                                                    <p class="text-sm font-semibold">${digitalData.engagement.time_spent_estimated || 'N/A'}</p>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-400">Score: <span class="font-semibold">${parseFloat(digitalData.engagement.engagement_score || 0).toFixed(0)}%</span></p>
                                            ${digitalData.engagement.interpretation ? `<p class="text-xs text-gray-300 mt-2">${digitalData.engagement.interpretation}</p>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${digitalData.reach ? `
                                        <div>
                                            <p class="text-xs text-gray-400 mb-1">Alcance</p>
                                            <div class="grid grid-cols-3 gap-2 mb-2">
                                                <div>
                                                    <p class="text-xs text-gray-400">Orgânico</p>
                                                    <p class="text-sm font-semibold">${(digitalData.reach.organic_reach_estimated || 0).toLocaleString('pt-BR')}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Pago</p>
                                                    <p class="text-sm font-semibold">${(digitalData.reach.paid_reach_estimated || 0).toLocaleString('pt-BR')}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Total</p>
                                                    <p class="text-sm font-semibold">${(digitalData.reach.total_reach_estimated || 0).toLocaleString('pt-BR')}</p>
                                                </div>
                                            </div>
                                            ${digitalData.reach.reach_effectiveness ? `<p class="text-xs text-gray-300">${digitalData.reach.reach_effectiveness}</p>` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                            
                            <!-- Dados Comportamentais do Público -->
                            ${behavioralData.purchase_intentions || behavioralData.complaints || behavioralData.social_feedback || behavioralData.product_reviews ? `
                                <div class="bg-gray-900 p-4 rounded border-l-4 border-purple-500">
                                    <h4 class="font-semibold mb-3 text-purple-400">👥 Dados Comportamentais do Público</h4>
                                    
                                    ${behavioralData.purchase_intentions ? `
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-400 mb-1">Intenções de Compra</p>
                                            <p class="text-sm font-semibold">Nível: <span class="${getLevelClass(behavioralData.purchase_intentions.level)}">${behavioralData.purchase_intentions.level || 'N/A'}</span></p>
                                            <p class="text-xs text-gray-400">Tendência: ${behavioralData.purchase_intentions.trend || 'N/A'}</p>
                                            ${behavioralData.purchase_intentions.indicators && behavioralData.purchase_intentions.indicators.length > 0 ? `
                                                <p class="text-xs text-gray-300 mt-1">Indicadores: ${behavioralData.purchase_intentions.indicators.join(', ')}</p>
                                            ` : ''}
                                            ${behavioralData.purchase_intentions.interpretation ? `<p class="text-xs text-gray-300 mt-2">${behavioralData.purchase_intentions.interpretation}</p>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${behavioralData.complaints ? `
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-400 mb-1">Reclamações</p>
                                            <p class="text-sm font-semibold">Total: ${behavioralData.complaints.count || 0}</p>
                                            <p class="text-xs text-gray-400">Tendência: ${behavioralData.complaints.trend || 'N/A'}</p>
                                            ${behavioralData.complaints.main_categories && behavioralData.complaints.main_categories.length > 0 ? `
                                                <div class="mt-2 space-y-1">
                                                    ${behavioralData.complaints.main_categories.map(cat => `
                                                        <div class="text-xs">
                                                            <span class="font-semibold">${cat.category || 'N/A'}</span>: ${cat.count || 0} (${cat.severity || 'N/A'})
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            ` : ''}
                                            ${behavioralData.complaints.interpretation ? `<p class="text-xs text-gray-300 mt-2">${behavioralData.complaints.interpretation}</p>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${behavioralData.social_feedback ? `
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-400 mb-1">Feedback em Redes Sociais</p>
                                            <div class="grid grid-cols-3 gap-2 mb-2">
                                                <div>
                                                    <p class="text-xs text-gray-400">Positivo</p>
                                                    <p class="text-sm font-semibold text-green-400">${behavioralData.social_feedback.positive_feedback_count || 0}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Negativo</p>
                                                    <p class="text-sm font-semibold text-red-400">${behavioralData.social_feedback.negative_feedback_count || 0}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Neutro</p>
                                                    <p class="text-sm font-semibold text-yellow-400">${behavioralData.social_feedback.neutral_feedback_count || 0}</p>
                                                </div>
                                            </div>
                                            ${behavioralData.social_feedback.main_topics && behavioralData.social_feedback.main_topics.length > 0 ? `
                                                <p class="text-xs text-gray-300">Tópicos: ${behavioralData.social_feedback.main_topics.join(', ')}</p>
                                            ` : ''}
                                            ${behavioralData.social_feedback.interpretation ? `<p class="text-xs text-gray-300 mt-2">${behavioralData.social_feedback.interpretation}</p>` : ''}
                                        </div>
                                    ` : ''}
                                    
                                    ${behavioralData.product_reviews ? `
                                        <div>
                                            <p class="text-xs text-gray-400 mb-1">Avaliações de Produtos</p>
                                            <div class="grid grid-cols-2 gap-2 mb-2">
                                                <div>
                                                    <p class="text-xs text-gray-400">Avaliação Média</p>
                                                    <p class="text-sm font-semibold">${parseFloat(behavioralData.product_reviews.average_rating_estimated || 0).toFixed(1)}/5</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Total Avaliações</p>
                                                    <p class="text-sm font-semibold">${behavioralData.product_reviews.review_count_estimated || 0}</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 mb-2">
                                                <div>
                                                    <p class="text-xs text-gray-400">Positivas</p>
                                                    <p class="text-sm font-semibold text-green-400">${parseFloat(behavioralData.product_reviews.positive_reviews_percentage || 0).toFixed(1)}%</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-400">Negativas</p>
                                                    <p class="text-sm font-semibold text-red-400">${parseFloat(behavioralData.product_reviews.negative_reviews_percentage || 0).toFixed(1)}%</p>
                                                </div>
                                            </div>
                                            ${behavioralData.product_reviews.main_concerns && behavioralData.product_reviews.main_concerns.length > 0 ? `
                                                <p class="text-xs text-gray-300">Preocupações: ${behavioralData.product_reviews.main_concerns.join(', ')}</p>
                                            ` : ''}
                                            ${behavioralData.product_reviews.interpretation ? `<p class="text-xs text-gray-300 mt-2">${behavioralData.product_reviews.interpretation}</p>` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                            
                            <!-- Insights Estratégicos -->
                            ${strategicInsights.length > 0 ? `
                                <div class="bg-indigo-900 bg-opacity-30 p-4 rounded border border-indigo-500">
                                    <h4 class="font-semibold mb-3 text-indigo-300">🎯 Insights Estratégicos</h4>
                                    <div class="space-y-3">
                                        ${strategicInsights.map(insight => `
                                            <div class="bg-gray-800 p-3 rounded">
                                                <div class="flex items-start justify-between mb-2">
                                                    <p class="font-semibold text-sm flex-1">${insight.insight || 'N/A'}</p>
                                                    <div class="ml-2 text-right">
                                                        <span class="px-2 py-1 rounded text-xs ${getCategoryClass(insight.category)}">${insight.category || 'N/A'}</span>
                                                        <span class="px-2 py-1 rounded text-xs ${getPriorityClass(insight.priority)} ml-1">${insight.priority || 'N/A'}</span>
                                                    </div>
                                                </div>
                                                ${insight.evidence ? `<p class="text-xs text-gray-400 mb-1">Evidências: ${insight.evidence}</p>` : ''}
                                                ${insight.recommendation ? `<p class="text-xs text-blue-300 mt-1">💡 ${insight.recommendation}</p>` : ''}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <!-- Otimização de Custos -->
                            ${costOptimization.areas_to_cut || costOptimization.areas_to_invest || costOptimization.strategic_recommendation ? `
                                <div class="bg-amber-900 bg-opacity-30 p-4 rounded border border-amber-500">
                                    <h4 class="font-semibold mb-3 text-amber-300">💰 Otimização de Custos e Investimentos</h4>
                                    
                                    ${costOptimization.areas_to_cut && costOptimization.areas_to_cut.length > 0 ? `
                                        <div class="mb-3">
                                            <h5 class="text-sm font-semibold text-red-300 mb-2">🔻 Áreas para Cortar Custos</h5>
                                            <div class="space-y-2">
                                                ${costOptimization.areas_to_cut.map(area => `
                                                    <div class="bg-gray-800 p-3 rounded">
                                                        <p class="font-semibold text-sm">${area.area || 'N/A'}</p>
                                                        <p class="text-xs text-gray-400">Economia Potencial: <span class="text-green-400 font-semibold">${area.potential_savings || 'N/A'}</span></p>
                                                        <p class="text-xs text-gray-400">Impacto: ${area.impact || 'N/A'}</p>
                                                        ${area.recommendation ? `<p class="text-xs text-gray-300 mt-1">${area.recommendation}</p>` : ''}
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    ` : ''}
                                    
                                    ${costOptimization.areas_to_invest && costOptimization.areas_to_invest.length > 0 ? `
                                        <div class="mb-3">
                                            <h5 class="text-sm font-semibold text-green-300 mb-2">🔺 Áreas para Investir</h5>
                                            <div class="space-y-2">
                                                ${costOptimization.areas_to_invest.map(area => `
                                                    <div class="bg-gray-800 p-3 rounded">
                                                        <p class="font-semibold text-sm">${area.area || 'N/A'}</p>
                                                        <p class="text-xs text-gray-400">Retorno Potencial: <span class="text-green-400 font-semibold">${area.potential_return || 'N/A'}</span></p>
                                                        <p class="text-xs text-gray-400">Prioridade: <span class="${getPriorityClass(area.priority)}">${area.priority || 'N/A'}</span></p>
                                                        ${area.recommendation ? `<p class="text-xs text-gray-300 mt-1">${area.recommendation}</p>` : ''}
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    ` : ''}
                                    
                                    ${costOptimization.strategic_recommendation ? `
                                        <div class="bg-gray-800 p-3 rounded border-l-4 border-amber-500">
                                            <h5 class="text-sm font-semibold text-amber-300 mb-1">📊 Recomendação Estratégica</h5>
                                            <p class="text-xs text-gray-300">${costOptimization.strategic_recommendation}</p>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                            
                            <!-- Recomendação -->
                            ${analysis.recommendation ? `
                                <div class="bg-gray-900 p-4 rounded">
                                    <h4 class="font-semibold mb-2 text-orange-400">🎯 Recomendação</h4>
                                    <p class="text-sm text-gray-300">${analysis.recommendation}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('');
            }
        } catch (error) {
            container.innerHTML = `<p class="text-red-500 text-center py-8">Erro: ${error.message}</p>`;
        }
    }

    function getSentimentClass(sentiment) {
        const sent = (sentiment || '').toLowerCase();
        if (sent.includes('positivo') || sent === 'positive') return 'bg-green-500 text-white';
        if (sent.includes('negativo') || sent === 'negative') return 'bg-red-500 text-white';
        return 'bg-yellow-500 text-white';
    }

    function getImpactClass(impact) {
        const imp = (impact || '').toLowerCase();
        if (imp === 'alto' || imp === 'high') return 'bg-red-500 text-white';
        if (imp === 'médio' || imp === 'medium') return 'bg-yellow-500 text-white';
        return 'bg-gray-500 text-white';
    }

    function getPriorityClass(priority) {
        const pri = (priority || '').toLowerCase();
        if (pri === 'alta' || pri === 'high') return 'text-red-400 font-bold';
        if (pri === 'média' || pri === 'medium') return 'text-yellow-400';
        return 'text-gray-400';
    }

    function getSeverityClass(severity) {
        const sev = (severity || '').toLowerCase();
        if (sev === 'crítica' || sev === 'critical') return 'text-red-500 font-bold';
        if (sev === 'alta' || sev === 'high') return 'text-orange-500';
        if (sev === 'média' || sev === 'medium') return 'text-yellow-500';
        return 'text-gray-400';
    }

    function getLevelClass(level) {
        const lev = (level || '').toLowerCase();
        if (lev === 'alto' || lev === 'high') return 'text-green-400 font-bold';
        if (lev === 'médio' || lev === 'medium') return 'text-yellow-400';
        return 'text-gray-400';
    }

    function getCategoryClass(category) {
        const cat = (category || '').toLowerCase();
        if (cat === 'preço' || cat === 'price') return 'bg-red-500 text-white';
        if (cat === 'concorrência' || cat === 'competition') return 'bg-orange-500 text-white';
        if (cat === 'tendência' || cat === 'trend') return 'bg-blue-500 text-white';
        if (cat === 'satisfação' || cat === 'satisfaction') return 'bg-green-500 text-white';
        if (cat === 'custos' || cat === 'costs') return 'bg-purple-500 text-white';
        if (cat === 'investimento' || cat === 'investment') return 'bg-indigo-500 text-white';
        return 'bg-gray-500 text-white';
    }
</script>
@endpush
@endsection
