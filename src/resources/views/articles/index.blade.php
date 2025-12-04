@extends('layouts.app')

@section('title', 'Artigos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Artigos</h1>
            <p class="text-gray-400 mt-1">Gerencie todas as matérias geradas</p>
        </div>
        <div class="flex gap-2">
            <select id="statusFilter" onchange="loadArticles()" class="bg-gray-800 border border-gray-600 rounded px-4 py-2 text-white">
                <option value="">Todos os status</option>
                <option value="pendente_revisao">Pendente</option>
                <option value="aprovado">Aprovado</option>
                <option value="reprovado">Reprovado</option>
                <option value="publicado">Publicado</option>
            </select>
            <input type="text" id="symbolFilter" onkeyup="loadArticles()" placeholder="Filtrar por ticker..." class="bg-gray-800 border border-gray-600 rounded px-4 py-2 text-white">
        </div>
    </div>

    <div id="articles-container" class="space-y-4">
        <p class="text-gray-500 text-center py-8">Carregando artigos...</p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', loadArticles);

    async function loadArticles() {
        const status = document.getElementById('statusFilter').value;
        const symbol = document.getElementById('symbolFilter').value;
        const container = document.getElementById('articles-container');
        
        container.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando...</p>';
        
        try {
            let url = `${window.API_URL}/articles?per_page=20&order_by=created_at&order_dir=desc`;
            if (status) url += `&status=${status}`;
            if (symbol) url += `&symbol=${symbol}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data) {
                const articles = Array.isArray(data.data.data) ? data.data.data : data.data;
                
                if (articles.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum artigo encontrado</p>';
                    return;
                }
                
                container.innerHTML = articles.map(article => `
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700 hover:border-gray-600 transition-colors">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-2">${article.title || 'Sem título'}</h3>
                                <div class="flex gap-4 text-sm text-gray-400">
                                    <span>${article.symbol}</span>
                                    <span>${new Date(article.created_at).toLocaleDateString('pt-BR')}</span>
                                    ${article.reviewed_at ? `<span>Revisado: ${new Date(article.reviewed_at).toLocaleDateString('pt-BR')}</span>` : ''}
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded text-xs font-bold ${getStatusClass(article.status)}">
                                ${getStatusText(article.status)}
                            </span>
                        </div>
                        <p class="text-gray-300 mb-4 line-clamp-3">${article.content ? article.content.substring(0, 200) + '...' : 'Sem conteúdo'}</p>
                        <div class="flex gap-2">
                            <a href="/articles/${article.id}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-semibold">
                                Ver Detalhes
                            </a>
                            ${article.status === 'pendente_revisao' ? `
                                <button onclick="approveArticle(${article.id})" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-semibold">
                                    Aprovar
                                </button>
                                <button onclick="rejectArticle(${article.id})" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded text-sm font-semibold">
                                    Reprovar
                                </button>
                            ` : ''}
                            ${article.status === 'aprovado' ? `
                                <button onclick="publishArticle(${article.id})" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded text-sm font-semibold">
                                    Publicar
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `).join('');
            }
        } catch (error) {
            container.innerHTML = `<p class="text-red-500 text-center py-8">Erro ao carregar artigos: ${error.message}</p>`;
        }
    }

    async function approveArticle(id) {
        if (!confirm('Aprovar este artigo?')) return;
        
        try {
            const data = await window.apiCall(`/articles/${id}/approve`, 'POST', null, true);
            
            if (data.success) {
                alert('Artigo aprovado com sucesso!');
                loadArticles();
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
                loadArticles();
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

    async function publishArticle(id) {
        if (!confirm('Publicar este artigo?')) return;
        
        try {
            const data = await window.apiCall(`/articles/${id}/publish`, 'POST', null, true);
            
            if (data.success) {
                alert('Artigo publicado com sucesso!');
                loadArticles();
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
                        await publishArticle(id);
                    } catch (e) {
                        alert(`Erro ao fazer login: ${e.message}`);
                    }
                }
            } else {
                alert(`Erro: ${error.message}`);
            }
        }
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
</script>
@endpush
@endsection

