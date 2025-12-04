@extends('layouts.app')

@section('title', 'Controle de Agentes')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Controle de Agentes</h1>
            <p class="text-gray-400 mt-1">Execute e monitore os agentes de IA</p>
        </div>
    </div>

    <!-- Controle de Execução -->
    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
        <h2 class="text-xl font-bold mb-4">Executar Agentes</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400 uppercase font-bold tracking-wider block mb-2">Ticker (Opcional)</label>
                <input type="text" id="agentTicker" class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 uppercase font-mono" placeholder="Ex: Petrobras">
            </div>
            <div class="flex items-end gap-2">
                <button onclick="executeAll()" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded font-semibold">
                    Executar Todos
                </button>
            </div>
        </div>
    </div>

    <!-- Agentes -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Agente Júlia -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-900 flex items-center justify-center text-purple-200 font-bold">J</div>
                    <div>
                        <h3 class="font-bold">Agente Júlia</h3>
                        <p class="text-xs text-gray-400">Coleta Financeira</p>
                    </div>
                </div>
                <button onclick="executeAgent('julia')" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded text-sm font-semibold">
                    Executar
                </button>
            </div>
            <div id="julia-output" class="text-sm font-mono text-gray-400 bg-gray-900 p-3 rounded min-h-[100px]">
                <p>Aguardando execução...</p>
            </div>
        </div>

        <!-- Agente Pedro -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-orange-900 flex items-center justify-center text-orange-200 font-bold">P</div>
                    <div>
                        <h3 class="font-bold">Agente Pedro</h3>
                        <p class="text-xs text-gray-400">Análise de Sentimento</p>
                    </div>
                </div>
                <button onclick="executeAgent('pedro')" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded text-sm font-semibold">
                    Executar
                </button>
            </div>
            <div id="pedro-output" class="text-sm font-mono text-gray-400 bg-gray-900 p-3 rounded min-h-[100px]">
                <p>Aguardando execução...</p>
            </div>
        </div>

        <!-- Agente Key -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-900 flex items-center justify-center text-green-200 font-bold">K</div>
                    <div>
                        <h3 class="font-bold">Agente Key</h3>
                        <p class="text-xs text-gray-400">Jornalista (LLM)</p>
                    </div>
                </div>
                <button onclick="executeAgent('key')" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-semibold">
                    Executar
                </button>
            </div>
            <div id="key-output" class="text-sm font-mono text-gray-400 bg-gray-900 p-3 rounded min-h-[100px]">
                <p>Aguardando execução...</p>
            </div>
        </div>

        <!-- Agente PublishNotify -->
        <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-yellow-900 flex items-center justify-center text-yellow-200 font-bold">N</div>
                    <div>
                        <h3 class="font-bold">Agente Notify</h3>
                        <p class="text-xs text-gray-400">Notificações</p>
                    </div>
                </div>
                <button onclick="executeAgent('publish-notify')" class="bg-yellow-600 hover:bg-yellow-500 text-white px-4 py-2 rounded text-sm font-semibold">
                    Executar
                </button>
            </div>
            <div id="publish-notify-output" class="text-sm font-mono text-gray-400 bg-gray-900 p-3 rounded min-h-[100px]">
                <p>Aguardando execução...</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function executeAgent(agent) {
        const output = document.getElementById(`${agent}-output`);
        const ticker = document.getElementById('agentTicker').value.toUpperCase();
        
        output.innerHTML = '<p class="text-yellow-400">Executando...</p>';
        
        try {
            const body = ticker ? { symbol: ticker } : {};
            const data = await apiCall(`/agents/${agent}`, 'POST', body);
            
            output.innerHTML = `
                <p class="text-green-400">✓ ${data.message || 'Executado com sucesso'}</p>
                <pre class="mt-2 text-xs">${data.output || 'Sem saída'}</pre>
            `;
        } catch (error) {
            output.innerHTML = `<p class="text-red-400">✗ Erro: ${error.message}</p>`;
        }
    }

    async function executeAll() {
        const ticker = document.getElementById('agentTicker').value.toUpperCase();
        
        if (!confirm('Executar todos os agentes em sequência?')) return;
        
        try {
            await executeAgent('julia');
            await delay(2000);
            await executeAgent('pedro');
            await delay(2000);
            await executeAgent('key');
            alert('Todos os agentes foram executados!');
        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
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

