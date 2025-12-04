@extends('layouts.app')

@section('title', 'Dados Financeiros')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Dados Financeiros</h1>
            <p class="text-gray-400 mt-1">Coletados pelo Agente Júlia</p>
        </div>
        <input type="text" id="symbolFilter" onkeyup="loadFinancialData()" placeholder="Filtrar por ticker..." class="bg-gray-800 border border-gray-600 rounded px-4 py-2 text-white">
    </div>

    <div id="financial-data-container" class="space-y-4">
        <p class="text-gray-500 text-center py-8">Carregando dados financeiros...</p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', loadFinancialData);

    async function loadFinancialData() {
        const symbol = document.getElementById('symbolFilter').value;
        const container = document.getElementById('financial-data-container');
        
        container.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando...</p>';
        
        try {
            let url = `${window.API_URL}/financial-data?per_page=20&order_by=collected_at&order_dir=desc`;
            if (symbol) url += `&symbol=${symbol}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data) {
                const financialData = Array.isArray(data.data.data) ? data.data.data : data.data;
                
                if (financialData.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum dado financeiro encontrado</p>';
                    return;
                }
                
                container.innerHTML = financialData.map(item => `
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold">${item.symbol}</h3>
                                <p class="text-sm text-gray-400 mt-1">
                                    Coletado: ${new Date(item.collected_at).toLocaleString('pt-BR')}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">R$ ${parseFloat(item.price || 0).toFixed(2)}</p>
                                <p class="text-sm ${(item.change_percent || 0) >= 0 ? 'text-green-400' : 'text-red-400'}">
                                    ${(item.change_percent || 0) >= 0 ? '+' : ''}${parseFloat(item.change_percent || 0).toFixed(2)}%
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-400">Volume</p>
                                <p class="font-semibold">${formatNumber(item.volume || 0)}</p>
                            </div>
                            <div>
                                <p class="text-gray-400">Alta</p>
                                <p class="font-semibold">R$ ${parseFloat(item.high_52w || 0).toFixed(2)}</p>
                            </div>
                            <div>
                                <p class="text-gray-400">Baixa</p>
                                <p class="font-semibold">R$ ${parseFloat(item.low_52w || 0).toFixed(2)}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            container.innerHTML = `<p class="text-red-500 text-center py-8">Erro: ${error.message}</p>`;
        }
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('pt-BR').format(num);
    }
</script>
@endpush
@endsection

