@extends('layouts.app')

@section('title', 'Detalhes do Artigo')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('articles.index') }}" class="text-blue-400 hover:text-blue-300 mb-4 inline-block">
                ← Voltar para Artigos
            </a>
            <h1 class="text-3xl font-bold">Detalhes do Artigo</h1>
        </div>
    </div>

    <div id="article-container" class="space-y-6">
        <p class="text-gray-500 text-center py-8">Carregando artigo...</p>
    </div>
</div>

@push('scripts')
<script>
    const articleId = {{ $id }};
    
    document.addEventListener('DOMContentLoaded', loadArticle);

    async function loadArticle() {
        const container = document.getElementById('article-container');
        
        try {
            const response = await fetch(`${window.API_URL}/articles/${articleId}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const article = data.data;
                
                container.innerHTML = `
                    <div class="bg-gray-800 p-8 rounded-lg border border-gray-700">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex-1">
                                <h2 class="text-3xl font-bold mb-4">${article.title || 'Sem título'}</h2>
                                <div class="flex gap-4 text-sm text-gray-400 mb-4">
                                    <span><strong>Ticker:</strong> ${article.symbol}</span>
                                    <span><strong>Criado:</strong> ${new Date(article.created_at).toLocaleString('pt-BR')}</span>
                                    ${article.reviewed_at ? `<span><strong>Revisado:</strong> ${new Date(article.reviewed_at).toLocaleString('pt-BR')}</span>` : ''}
                                    ${article.published_at ? `<span><strong>Publicado:</strong> ${new Date(article.published_at).toLocaleString('pt-BR')}</span>` : ''}
                                </div>
                            </div>
                            <span class="px-4 py-2 rounded text-sm font-bold ${getStatusClass(article.status)}">
                                ${getStatusText(article.status)}
                            </span>
                        </div>
                        
                        <div class="bg-gray-900 p-6 rounded-lg mb-6">
                            <div class="prose prose-invert max-w-none">
                                ${formatContent(article.content || 'Sem conteúdo')}
                            </div>
                        </div>
                        
                        ${article.recomendacao ? `
                            <div class="bg-blue-900/30 border-l-4 border-blue-500 p-4 mb-6">
                                <p class="font-bold text-blue-300 mb-2">Recomendação:</p>
                                <p class="text-blue-200">${article.recomendacao}</p>
                            </div>
                        ` : ''}
                        
                        ${article.motivo_reprovacao ? `
                            <div class="bg-red-900/30 border-l-4 border-red-500 p-4 mb-6">
                                <p class="font-bold text-red-300 mb-2">Motivo da Reprovação:</p>
                                <p class="text-red-200">${article.motivo_reprovacao}</p>
                            </div>
                        ` : ''}
                        
                        <div class="flex gap-4">
                            ${article.status === 'pendente_revisao' ? `
                                <button onclick="approveArticle(${article.id})" class="flex-1 bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded font-semibold">
                                    ✓ Aprovar
                                </button>
                                <button onclick="rejectArticle(${article.id})" class="flex-1 bg-red-600 hover:bg-red-500 text-white px-6 py-3 rounded font-semibold">
                                    ✗ Reprovar
                                </button>
                            ` : ''}
                            ${article.status === 'aprovado' ? `
                                <button onclick="publishArticle(${article.id})" class="flex-1 bg-purple-600 hover:bg-purple-500 text-white px-6 py-3 rounded font-semibold">
                                    📢 Publicar
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = '<p class="text-red-500 text-center py-8">Artigo não encontrado</p>';
            }
        } catch (error) {
            container.innerHTML = `<p class="text-red-500 text-center py-8">Erro ao carregar artigo: ${error.message}</p>`;
        }
    }

    function formatContent(content) {
        if (!content) return '<p class="text-gray-500">Sem conteúdo</p>';
        
        // Se já é HTML, retorna como está
        if (content.includes('<') && content.includes('>')) {
            return content;
        }
        
        // Caso contrário, trata como Markdown/texto simples
        return content.split('\n').map(para => {
            if (!para.trim()) return '';
            if (para.trim().startsWith('#')) {
                const level = para.match(/^#+/)[0].length;
                const text = para.replace(/^#+\s*/, '');
                return `<h${level} class="text-${level === 1 ? '3xl' : level === 2 ? '2xl' : 'xl'} font-bold mb-4 mt-6">${text}</h${level}>`;
            }
            return `<p class="mb-4">${para}</p>`;
        }).join('');
    }

    async function approveArticle(id) {
        if (!confirm('Aprovar este artigo?')) return;
        
        try {
            const data = await window.apiCall(`/articles/${id}/approve`, 'POST', null, true);
            
            if (data.success) {
                alert('Artigo aprovado com sucesso!');
                loadArticle();
            } else {
                alert(`Erro: ${data.message}`);
            }
        } catch (error) {
            if (error.message.includes('Autenticação')) {
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
                loadArticle();
            } else {
                alert(`Erro: ${data.message}`);
            }
        } catch (error) {
            if (error.message.includes('Autenticação')) {
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
                loadArticle();
            } else {
                alert(`Erro: ${data.message}`);
            }
        } catch (error) {
            if (error.message.includes('Autenticação')) {
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
</script>
@endpush
@endsection

