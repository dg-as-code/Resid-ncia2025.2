@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Dashboard</h1>
            <p class="text-gray-400 mt-1">Visão geral do sistema de agentes</p>
        </div>
    </div>

    <!-- Status dos Agentes -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Agente Júlia -->
        <div id="card-julia" class="relative bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="scanning-line"></div>
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-purple-900 flex items-center justify-center text-purple-200 font-bold text-xl">J</div>
                    <div>
                        <h3 class="font-bold text-lg">Agente Júlia</h3>
                        <p class="text-xs text-gray-400">Coleta de dados financeiros</p>
                    </div>
                </div>
                <span id="status-julia" class="text-xs px-2 py-1 rounded bg-gray-700 text-gray-400">Aguardando</span>
            </div>
            <div id="data-julia" class="text-sm font-mono text-gray-300">
                <p class="text-gray-500">Aguardando execução...</p>
            </div>
        </div>

        <!-- Agente Pedro -->
        <div id="card-pedro" class="relative bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="scanning-line"></div>
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-orange-900 flex items-center justify-center text-orange-200 font-bold text-xl">P</div>
                    <div>
                        <h3 class="font-bold text-lg">Agente Pedro</h3>
                        <p class="text-xs text-gray-400">Análise de macroeconomia</p>
                    </div>
                </div>
                <span id="status-pedro" class="text-xs px-2 py-1 rounded bg-gray-700 text-gray-400">Aguardando</span>
            </div>
            <div id="data-pedro" class="text-sm font-mono text-gray-300">
                <p class="text-gray-500">Aguardando execução...</p>
            </div>
        </div>

        <!-- Agente Key -->
        <div id="card-key" class="relative bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="scanning-line"></div>
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-green-900 flex items-center justify-center text-green-200 font-bold text-xl">K</div>
                    <div>
                        <h3 class="font-bold text-lg">Agente Key</h3>
                        <p class="text-xs text-gray-400">Jornalista/Redatora</p>
                    </div>
                </div>
                <span id="status-key" class="text-xs px-2 py-1 rounded bg-gray-700 text-gray-400">Aguardando</span>
            </div>
            <div id="data-key" class="text-sm font-mono text-gray-300">
                <p class="text-gray-500">Aguardando execução...</p>
            </div>
        </div>
    </div>

    <!-- Controle Rápido - Orquestração Completa -->
    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Execução Rápida (Orquestração)</h2>
            <a href="{{ route('orchestrate') }}" class="text-sm text-blue-400 hover:text-blue-300 flex items-center gap-2">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                Ver página completa
            </a>
        </div>
        <div class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="text-xs text-gray-400 uppercase font-bold tracking-wider block mb-2">Nome da Empresa</label>
                <input type="text" id="companyNameInput" value="Petrobras" class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 font-mono" placeholder="Ex: Petrobras, Apple, Tesla">
            </div>
            <button onclick="startOrchestration()" id="btnStartOrchestration" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded flex items-center gap-2 transition-all font-semibold">
                <i data-lucide="play" class="w-5 h-5"></i>
                Executar Orquestração
            </button>
        </div>
        
        <!-- Logs em Tempo Real -->
        <div id="orchestrationLogs" class="mt-4 bg-gray-900 rounded p-4 border border-gray-700 hidden">
            <h3 class="text-sm font-bold text-gray-400 mb-2 uppercase tracking-wider">Logs do Sistema</h3>
            <div id="logsContainer" class="font-mono text-xs text-green-400 space-y-1 max-h-48 overflow-y-auto">
            </div>
        </div>
        
        <!-- Resultado da Orquestração -->
        <div id="orchestrationResult" class="mt-4 hidden">
            <div class="bg-gray-900 rounded p-4 border border-gray-700">
                <h3 class="text-sm font-bold text-gray-400 mb-2 uppercase tracking-wider">Resultado</h3>
                <div id="resultContent" class="text-sm"></div>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Artigos Pendentes</p>
                    <p id="stat-pending" class="text-2xl font-bold mt-2">-</p>
                </div>
                <i data-lucide="clock" class="w-8 h-8 text-yellow-500"></i>
            </div>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Artigos Aprovados</p>
                    <p id="stat-approved" class="text-2xl font-bold mt-2">-</p>
                </div>
                <i data-lucide="check-circle" class="w-8 h-8 text-green-500"></i>
            </div>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Artigos Publicados</p>
                    <p id="stat-published" class="text-2xl font-bold mt-2">-</p>
                </div>
                <i data-lucide="send" class="w-8 h-8 text-blue-500"></i>
            </div>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total de Artigos</p>
                    <p id="stat-total" class="text-2xl font-bold mt-2">-</p>
                </div>
                <i data-lucide="file-text" class="w-8 h-8 text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Artigos Recentes -->
    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
        <h2 class="text-xl font-bold mb-4">Artigos Recentes</h2>
        <div id="recent-articles" class="space-y-4">
            <p class="text-gray-500 text-center py-8">Carregando artigos...</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Carregar estatísticas ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        loadStatistics();
        loadRecentArticles();
        loadAgentsStatus();
    });

    async function loadStatistics() {
        try {
            const response = await fetch(`${window.API_URL}/articles`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const articles = Array.isArray(data.data.data) ? data.data.data : data.data;
                
                const stats = {
                    pending: articles.filter(a => a.status === 'pendente_revisao').length,
                    approved: articles.filter(a => a.status === 'aprovado').length,
                    published: articles.filter(a => a.status === 'publicado').length,
                    total: articles.length
                };
                
                document.getElementById('stat-pending').textContent = stats.pending;
                document.getElementById('stat-approved').textContent = stats.approved;
                document.getElementById('stat-published').textContent = stats.published;
                document.getElementById('stat-total').textContent = stats.total;
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    async function loadRecentArticles() {
        try {
            const response = await fetch(`${window.API_URL}/articles?per_page=5&order_by=created_at&order_dir=desc`);
            const data = await response.json();
            
            const container = document.getElementById('recent-articles');
            
            if (data.success && data.data) {
                const articles = Array.isArray(data.data.data) ? data.data.data : data.data;
                
                if (articles.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum artigo encontrado</p>';
                    return;
                }
                
                container.innerHTML = articles.map(article => `
                    <div class="flex items-center justify-between p-4 bg-gray-900 rounded-lg border border-gray-700">
                        <div class="flex-1">
                            <h3 class="font-semibold">${article.title || 'Sem título'}</h3>
                            <p class="text-sm text-gray-400 mt-1">
                                ${article.symbol} • ${new Date(article.created_at).toLocaleDateString('pt-BR')}
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded text-xs font-bold ${getStatusClass(article.status)}">
                            ${getStatusText(article.status)}
                        </span>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Erro ao carregar artigos recentes:', error);
            document.getElementById('recent-articles').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar artigos</p>';
        }
    }

    async function loadAgentsStatus() {
        try {
            const response = await fetch(`${window.API_URL}/agents/status`);
            const data = await response.json();
            
            if (data.success && data.data) {
                console.log('Status dos agentes:', data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar status dos agentes:', error);
        }
    }

    // Função para adicionar log
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

    // Nova função de orquestração completa
    async function startOrchestration() {
        const companyName = document.getElementById('companyNameInput').value.trim();
        const btn = document.getElementById('btnStartOrchestration');
        const logsSection = document.getElementById('orchestrationLogs');
        const resultSection = document.getElementById('orchestrationResult');
        const logsContainer = document.getElementById('logsContainer');
        const resultContent = document.getElementById('resultContent');
        
        if (!companyName) {
            alert('Por favor, digite o nome da empresa');
            return;
        }
        
        // Limpa logs e resultados anteriores
        if (logsContainer) logsContainer.innerHTML = '';
        if (resultContent) resultContent.innerHTML = '';
        if (logsSection) logsSection.classList.remove('hidden');
        if (resultSection) resultSection.classList.add('hidden');
        
        // Reseta status dos agentes
        resetAllAgents();
        
        btn.disabled = true;
        btn.classList.add('opacity-50');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Processando...';
        
        addLog('Sistema', `Iniciando análise para: ${companyName}`);
        
        try {
            // Ativa visualmente os agentes
            activateAgent('julia');
            addLog('Julia', `Iniciado... Coletando dados de ${companyName}...`);
            
            // Chama endpoint de orquestração
            const response = await window.apiCall('/orchestrate', 'POST', { company_name: companyName });
            
            if (response.success) {
                // Exibe logs do backend
                if (response.logs && Array.isArray(response.logs)) {
                    response.logs.forEach(log => {
                        addLog(log.agent, log.message);
                    });
                }
                
                // Atualiza status dos agentes
                finishAgent('julia', 'Concluído', `Dados coletados`);
                activateAgent('pedro');
                finishAgent('pedro', 'Concluído', `Análise concluída`);
                activateAgent('key');
                finishAgent('key', 'Concluído', `Matéria gerada em HTML`);
                
                // Exibe resultado
                if (resultSection) {
                    resultSection.classList.remove('hidden');
                    resultContent.innerHTML = `
                        <div class="space-y-4">
                            <div class="bg-yellow-900 border-2 border-yellow-500 rounded-lg p-4">
                                <p class="text-lg font-semibold text-yellow-200 mb-2">📧 ${response.message}</p>
                                <p class="text-sm text-yellow-300 mb-4">Status: <strong>${response.status}</strong></p>
                                <div class="flex gap-4">
                                    <a href="/articles/${response.article_id}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold">
                                        Ver Artigo Completo
                                    </a>
                                    <a href="/articles/review" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-semibold">
                                        Ir para Revisão
                                    </a>
                                </div>
                            </div>
                            ${response.article ? `
                                <div class="bg-gray-800 rounded p-4">
                                    <h4 class="font-semibold mb-2">Título:</h4>
                                    <p class="text-gray-300">${response.article.title}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
                
                addLog('Sistema', 'Orquestração concluída. Aguardando revisão humana.');
                
                // Atualiza estatísticas
                loadStatistics();
                loadRecentArticles();
            } else {
                throw new Error(response.message || 'Erro desconhecido');
            }
        } catch (error) {
            addLog('Sistema', `Erro: ${error.message}`);
            alert(`Erro ao executar orquestração: ${error.message}`);
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50');
            btn.innerHTML = '<i data-lucide="play" class="w-5 h-5"></i> Executar Orquestração';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }
    
    // Função para resetar todos os agentes
    function resetAllAgents() {
        ['julia', 'pedro', 'key'].forEach(agent => {
            const card = document.getElementById(`card-${agent}`);
            if (card) {
                card.classList.remove('agent-active', 'border-blue-500', 'border-green-600');
                card.classList.add('border-gray-700');
            }
            const status = document.getElementById(`status-${agent}`);
            if (status) {
                status.innerText = 'Aguardando';
                status.className = 'text-xs px-2 py-1 rounded bg-gray-700 text-gray-400';
            }
            const data = document.getElementById(`data-${agent}`);
            if (data) {
                data.innerHTML = '<p class="text-gray-500">Aguardando execução...</p>';
            }
        });
    }

    function activateAgent(agent) {
        const card = document.getElementById(`card-${agent}`);
        card.classList.add('agent-active', 'border-blue-500');
        card.classList.remove('border-gray-700');
        
        const status = document.getElementById(`status-${agent}`);
        status.innerText = 'Trabalhando...';
        status.className = 'text-xs px-2 py-1 rounded bg-blue-900 text-blue-200 animate-pulse';
    }

    function finishAgent(agent, statusText, logText) {
        const card = document.getElementById(`card-${agent}`);
        card.classList.remove('agent-active', 'border-blue-500');
        card.classList.add('border-green-600');
        
        const status = document.getElementById(`status-${agent}`);
        status.innerText = statusText;
        status.className = 'text-xs px-2 py-1 rounded bg-green-900 text-green-200';
        
        const data = document.getElementById(`data-${agent}`);
        data.innerHTML = `<p>${logText}</p>`;
    }

    function getStatusClass(status) {
        const classes = {
            'pendente_revisao': 'bg-orange-100 text-orange-800',
            'aprovado': 'bg-green-100 text-green-800',
            'reprovado': 'bg-red-100 text-red-800',
            'publicado': 'bg-blue-100 text-blue-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    function getStatusText(status) {
        const texts = {
            'pendente_revisao': 'Pendente',
            'aprovado': 'Aprovado',
            'reprovado': 'Reprovado',
            'publicado': 'Publicado'
        };
        return texts[status] || status;
    }

    async function apiCall(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(`${window.API_URL}${endpoint}`, options);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `Erro ${response.status}`);
        }
        
        return data;
    }

    const delay = ms => new Promise(res => setTimeout(res, ms));
</script>
@endpush
@endsection

