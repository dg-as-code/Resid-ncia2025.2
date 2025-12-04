@extends('layouts.app')

@section('title', 'Revisão de Artigos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Revisão de Artigos</h1>
            <p class="text-gray-400 mt-1">Artigos pendentes de revisão humana</p>
        </div>
    </div>

    <div id="review-container" class="space-y-6">
        <p class="text-gray-500 text-center py-8">Carregando artigos pendentes...</p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', loadPendingArticles);

    async function loadPendingArticles() {
        const container = document.getElementById('review-container');
        
        try {
            const response = await fetch(`${window.API_URL}/articles?status=pendente_revisao&order_by=created_at&order_dir=desc`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const articles = Array.isArray(data.data.data) ? data.data.data : data.data;
                
                if (articles.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum artigo pendente de revisão</p>';
                    return;
                }
                
                container.innerHTML = articles.map(article => `
                    <div class="bg-gray-800 p-8 rounded-lg border border-gray-700">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-2xl font-bold mb-2">${article.title || 'Sem título'}</h2>
                                <div class="flex gap-4 text-sm text-gray-400">
                                    <span><strong>Ticker:</strong> ${article.symbol}</span>
                                    <span><strong>Criado:</strong> ${new Date(article.created_at).toLocaleString('pt-BR')}</span>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded text-xs font-bold bg-orange-100 text-orange-800">
                                Pendente de Revisão
                            </span>
                        </div>
                        
                        <div class="bg-gray-900 p-6 rounded-lg mb-6">
                            <div class="prose prose-invert max-w-none">
                                ${formatContent(article.content || 'Sem conteúdo')}
                            </div>
                        </div>
                        
                        ${article.recomendacao ? `
                            <div class="bg-blue-900/30 border-l-4 border-blue-500 p-4 mb-6">
                                <p class="font-bold text-blue-300">Recomendação:</p>
                                <p class="text-blue-200">${article.recomendacao}</p>
                            </div>
                        ` : ''}
                        
                        <div class="flex gap-4">
                            <button onclick="approveArticle(${article.id})" class="flex-1 bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded font-semibold">
                                ✓ Aprovar
                            </button>
                            <button onclick="rejectArticle(${article.id})" class="flex-1 bg-red-600 hover:bg-red-500 text-white px-6 py-3 rounded font-semibold">
                                ✗ Reprovar
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            container.innerHTML = `<p class="text-red-500 text-center py-8">Erro ao carregar artigos: ${error.message}</p>`;
        }
    }

    function formatContent(content) {
        if (!content) return '<p class="text-gray-500">Sem conteúdo</p>';
        
        // Se já é HTML, retorna como está
        if (content.includes('<') && content.includes('>')) {
            return content;
        }
        
        // Caso contrário, trata como texto simples
        return content.split('\n').map(para => {
            if (!para.trim()) return '';
            return `<p class="mb-4">${para}</p>`;
        }).join('');
    }

    async function approveArticle(id) {
        if (!confirm('Aprovar este artigo?')) return;
        
        try {
            const data = await window.apiCall(`/articles/${id}/approve`, 'POST', null, true);
            
            if (data.success) {
                alert('Artigo aprovado com sucesso!');
                loadPendingArticles();
            } else {
                alert(`Erro: ${data.message}`);
            }
        } catch (error) {
            if (error.message.includes('Autenticação') || error.message.includes('Authorization')) {
                const usuario = prompt('Usuário:');
                const senha = prompt('Senha:');
                if (usuario && senha) {
                    try {
                        await window.login(usuario, senha);
                        await approveArticle(id);
                    } catch (e) {
                        alert(`Erro ao fazer login: ${e.message}`);
                    }
                }
            } else {
                alert(`Erro: ${error.message}`);
            }
        }
    }

    async function rejectArticle(id) {
        const motivo = prompt('Motivo da reprovação:');
        if (!motivo) return;
        
        try {
            const data = await window.apiCall(`/articles/${id}/reject`, 'POST', { motivo_reprovacao: motivo }, true);
            
            if (data.success) {
                alert('Artigo reprovado.');
                loadPendingArticles();
            } else {
                alert(`Erro: ${data.message}`);
            }
        } catch (error) {
            if (error.message.includes('Autenticação') || error.message.includes('Authorization')) {
                const usuario = prompt('Usuário:');
                const senha = prompt('Senha:');
                if (usuario && senha) {
                    try {
                        await window.login(usuario, senha);
                        await rejectArticle(id);
                    } catch (e) {
                        alert(`Erro ao fazer login: ${e.message}`);
                    }
                }
            } else {
                alert(`Erro: ${error.message}`);
            }
        }
    }
</script>
@endpush
@endsection

