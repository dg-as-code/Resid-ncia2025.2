@extends('layouts.app')

@section('title', 'Orquestração de Agentes')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Orquestração de Agentes Financeiros</h1>
            <p class="text-gray-400 mt-1">Fluxo completo automatizado: Júlia → Pedro → Key → Revisão</p>
        </div>
    </div>
    
    <!-- Formulário de Entrada -->
    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
        <h2 class="text-xl font-bold mb-4">Iniciar Análise Completa</h2>
        <div class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="text-xs text-gray-400 uppercase font-bold tracking-wider block mb-2">Nome da Empresa</label>
                <input 
                    type="text" 
                    id="companyNameInput" 
                    placeholder="Digite o nome da empresa (ex: Petrobras, Apple, Tesla)"
                    class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 font-mono"
                    value=""
                />
            </div>
            <button 
                onclick="startOrchestration()" 
                id="btnStartOrchestration"
                class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded flex items-center gap-2 transition-all font-semibold"
            >
                <i data-lucide="play" class="w-5 h-5"></i>
                Iniciar Orquestração
            </button>
        </div>
    </div>

    <!-- Logs do Sistema -->
    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
        <h2 class="text-xl font-bold mb-4">📋 Logs do Sistema</h2>
        <div id="logsContainer" class="font-mono text-sm text-green-400 space-y-1 max-h-96 overflow-y-auto bg-gray-900 p-4 rounded border border-gray-700">
            <div class="text-gray-500">Aguardando início da análise...</div>
        </div>
    </div>

    <!-- Resultados dos Agentes -->
    <div id="resultsContainer" class="space-y-4 hidden">
        <!-- Dados Financeiros (Júlia) -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <h2 class="text-xl font-bold mb-4">📊 Dados Financeiros (Agente Júlia)</h2>
            <pre id="financialDataOutput" class="bg-gray-900 p-4 rounded overflow-x-auto text-sm text-green-400 font-mono border border-gray-700"></pre>
        </div>

        <!-- Análise de Sentimento (Pedro) -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <h2 class="text-xl font-bold mb-4">📰 Análise de Sentimento (Agente Pedro)</h2>
            <pre id="sentimentDataOutput" class="bg-gray-900 p-4 rounded overflow-x-auto text-sm text-green-400 font-mono border border-gray-700"></pre>
        </div>

        <!-- Matéria Gerada (Key) -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <h2 class="text-xl font-bold mb-4">✍️ Matéria Gerada (Agente Key)</h2>
            <div id="articleOutput" class="bg-gray-900 p-4 rounded border border-gray-700 prose prose-invert max-w-none text-gray-300"></div>
        </div>

        <!-- Revisão Humana -->
        <div id="reviewSection" class="bg-yellow-900 border-2 border-yellow-500 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-yellow-200">👤 Revisão Humana</h2>
            <p class="mb-4 text-lg text-yellow-100">📧 E-mail enviado para o editor. Aguardando aprovação...</p>
            <p class="mb-6 font-semibold text-xl text-yellow-200">Você APROVA ou REJEITA esta matéria?</p>
            
            <div class="flex gap-4">
                <button 
                    onclick="reviewDecision('approve')" 
                    id="btnApprove"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2"
                >
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    APROVAR
                </button>
                <button 
                    onclick="showRejectForm()" 
                    id="btnReject"
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2"
                >
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                    REPROVAR
                </button>
            </div>

            <!-- Formulário de Reprovação -->
            <div id="rejectForm" class="hidden mt-4">
                <label class="block text-sm font-semibold text-yellow-200 mb-2">Motivo da Reprovação</label>
                <textarea 
                    id="rejectReason" 
                    placeholder="Digite o motivo da reprovação..."
                    class="w-full bg-gray-900 text-white px-4 py-2 border border-gray-600 rounded-lg mb-4 focus:outline-none focus:border-red-500"
                    rows="3"
                ></textarea>
                <button 
                    onclick="reviewDecision('reject')" 
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors"
                >
                    Confirmar Reprovação
                </button>
            </div>
        </div>

        <!-- Resultado Final -->
        <div id="finalResult" class="hidden"></div>
    </div>
</div>

@push('scripts')
<script>
let currentArticleId = null;

function addLog(agent, message) {
    const logsContainer = document.getElementById('logsContainer');
    if (!logsContainer) return;
    
    const timestamp = new Date().toLocaleTimeString('pt-BR');
    const logEntry = document.createElement('div');
    logEntry.className = 'text-green-400';
    logEntry.textContent = `[${timestamp}] [FRONTEND LOG] Agente ${agent} ${message}`;
    logsContainer.appendChild(logEntry);
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

async function startOrchestration() {
    const companyName = document.getElementById('companyNameInput').value.trim();
    const btn = document.getElementById('btnStartOrchestration');
    
    if (!companyName) {
        alert('Por favor, digite o nome da empresa');
        return;
    }
    
    // Limpa logs anteriores
    const logsContainer = document.getElementById('logsContainer');
    if (logsContainer) logsContainer.innerHTML = '';
    
    const resultsContainer = document.getElementById('resultsContainer');
    if (resultsContainer) resultsContainer.classList.add('hidden');
    
    const reviewSection = document.getElementById('reviewSection');
    if (reviewSection) reviewSection.classList.remove('hidden');
    
    const finalResult = document.getElementById('finalResult');
    if (finalResult) finalResult.classList.add('hidden');
    
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Processando...';
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    addLog('Sistema', `Iniciando análise para: ${companyName}`);
    
    try {
        const response = await window.apiCall('/orchestrate', 'POST', { company_name: companyName });
        if (response.success) {
            // Exibe logs
            if (response.logs && Array.isArray(response.logs)) {
                response.logs.forEach(log => {
                    addLog(log.agent, log.message);
                });
            }
            
            // Exibe dados financeiros
            const financialOutput = document.getElementById('financialDataOutput');
            if (financialOutput && response.financial_data) {
                financialOutput.textContent = JSON.stringify(response.financial_data, null, 2);
            }
            
            // Exibe análise de sentimento
            const sentimentOutput = document.getElementById('sentimentDataOutput');
            if (sentimentOutput && response.sentiment_data) {
                sentimentOutput.textContent = JSON.stringify(response.sentiment_data, null, 2);
            }
            
            // Exibe artigo em HTML
            const articleOutput = document.getElementById('articleOutput');
            if (articleOutput && response.article && response.article.html_content) {
                articleOutput.innerHTML = response.article.html_content;
            }
            
            // Salva ID do artigo para revisão
            currentArticleId = response.article_id;
            
            // Mostra seção de resultados
            if (resultsContainer) resultsContainer.classList.remove('hidden');
            
            addLog('Sistema', response.message);
        } else {
            addLog('Sistema', `Erro: ${response.message}`);
            alert(`Erro: ${response.message}`);
        }
    } catch (error) {
        addLog('Sistema', `Erro: ${error.message}`);
        alert(`Erro ao executar orquestração: ${error.message}`);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="play" class="w-5 h-5"></i> Iniciar Orquestração';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function showRejectForm() {
    document.getElementById('rejectForm').classList.remove('hidden');
}

async function reviewDecision(decision) {
    if (!currentArticleId) {
        alert('Nenhum artigo para revisar');
        return;
    }
    
    if (decision === 'reject') {
        const motivo = document.getElementById('rejectReason').value.trim();
        if (!motivo) {
            alert('Por favor, digite o motivo da reprovação');
            return;
        }
    }
    
    const btnApprove = document.getElementById('btnApprove');
    const btnReject = document.getElementById('btnReject');
    btnApprove.disabled = true;
    btnReject.disabled = true;
    
    addLog('Sistema', `Processando decisão: ${decision}`);
    
    const body = {
        decision: decision
    };
    
    if (decision === 'reject') {
        body.motivo_reprovacao = document.getElementById('rejectReason').value.trim();
    }
    
    try {
        const response = await window.apiCall(`/orchestrate/${currentArticleId}/review`, 'POST', body, true);
        
        if (response.success) {
            // Exibe logs
            if (response.logs && Array.isArray(response.logs)) {
                response.logs.forEach(log => {
                    addLog(log.agent, log.message);
                });
            }
            
            // Esconde seção de revisão
            const reviewSection = document.getElementById('reviewSection');
            if (reviewSection) reviewSection.classList.add('hidden');
            
            // Mostra resultado final
            const finalResult = document.getElementById('finalResult');
            if (finalResult) {
                finalResult.classList.remove('hidden');
                
                if (response.status === 'published') {
                    finalResult.className = 'bg-green-900 border-2 border-green-500 rounded-lg p-6';
                    finalResult.innerHTML = `
                        <h2 class="text-xl font-bold mb-4 text-green-200">✅ Matéria Publicada</h2>
                        <p class="text-lg text-green-100 mb-4">${response.message}</p>
                        <p class="text-sm text-gray-400">ID do Artigo: ${response.article?.id || currentArticleId}</p>
                        <div class="mt-4">
                            <a href="/articles/${response.article?.id || currentArticleId}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold inline-block">
                                Ver Artigo Publicado
                            </a>
                        </div>
                    `;
                } else if (response.status === 'rejected') {
                    finalResult.className = 'bg-orange-900 border-2 border-orange-500 rounded-lg p-6';
                    finalResult.innerHTML = `
                        <h2 class="text-xl font-bold mb-4 text-orange-200">📋 Matéria Salva para Re-análise</h2>
                        <p class="text-lg text-orange-100 mb-4">${response.message}</p>
                        <p class="text-sm text-gray-400">Status: <strong>Saved for Review</strong></p>
                    `;
                }
            }
            
            addLog('Sistema', response.message);
        } else {
            addLog('Sistema', `Erro: ${response.message}`);
            alert(`Erro: ${response.message}`);
        }
    } catch (error) {
        addLog('Sistema', `Erro: ${error.message}`);
        
        // Tratamento de erro de autenticação
        if (error.message.includes('Autenticação') || error.message.includes('Authorization')) {
            const usuario = prompt('Usuário:');
            const senha = prompt('Senha:');
            if (usuario && senha) {
                try {
                    await window.login(usuario, senha);
                    // Retenta a operação após login
                    await reviewDecision(decision);
                    return;
                } catch (e) {
                    alert(`Erro ao fazer login: ${e.message}`);
                }
            }
        } else {
            alert(`Erro ao processar decisão: ${error.message}`);
        }
    } finally {
        btnApprove.disabled = false;
        btnReject.disabled = false;
    }
}
</script>
@endpush
@endsection

