<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Agentes de IA') - MercoViewer AI</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body { font-family: 'Inter', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .scanning-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #10b981;
            animation: scan 2s linear infinite;
            display: none;
        }
        @keyframes scan { 0% {top: 0;} 100% {top: 100%;} }
        .agent-active .scanning-line { display: block; }
        
        /* Scrollbar customizada */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        
        /* Prose para conteúdo de artigos */
        .prose { color: #e5e7eb; }
        .prose p { margin-bottom: 1rem; }
        .prose h1, .prose h2, .prose h3 { color: #f3f4f6; font-weight: 700; margin-top: 1.5rem; margin-bottom: 1rem; }
        .prose strong { color: #f9fafb; font-weight: 600; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-gray-800 border-r border-gray-700 z-50">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="cpu" class="text-blue-500"></i> MercoViewer AI
            </h1>
            <p class="text-xs text-gray-400 mt-1">Coletor de Dados Financeiros v1.0</p>
        </div>

        <nav class="p-4 space-y-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-blue-400' : 'text-gray-300' }}">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('articles.index') }}" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('articles.*') ? 'bg-gray-700 text-blue-400' : 'text-gray-300' }}">
                <i data-lucide="file-text"></i>
                <span>Artigos</span>
            </a>
            <a href="{{ route('articles.review') }}" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('articles.review') ? 'bg-gray-700 text-blue-400' : 'text-gray-300' }}">
                <i data-lucide="clipboard-check"></i>
                <span>Revisão</span>
            </a>
            <a href="{{ route('financial-data.index') }}" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('financial-data.*') ? 'bg-gray-700 text-blue-400' : 'text-gray-300' }}">
                <i data-lucide="trending-up"></i>
                <span>Dados Financeiros</span>
            </a>
            <a href="{{ route('sentiment.index') }}" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('sentiment.*') ? 'bg-gray-700 text-blue-400' : 'text-gray-300' }}">
                <i data-lucide="message-square"></i>
                <span>Análise de Sentimento</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen">
        <!-- Header -->
        <header class="h-16 border-b border-gray-700 bg-gray-800 flex justify-between items-center px-8 sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <span class="text-gray-400 text-sm">Status:</span>
                <span id="main-status" class="px-3 py-1 rounded-full text-xs font-bold bg-gray-700 text-gray-300">SISTEMA ATIVO</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-400">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-8">
            @yield('content')
        </div>
    </main>

    <!-- Scripts -->
    <script>
        // Função para fazer chamadas à API
        window.apiCall = async function(endpoint, method = 'GET', body = null, requiresAuth = false) {
            try {
                const options = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                };

                if (requiresAuth) {
                    const token = localStorage.getItem('auth_token');
                    if (token) {
                        options.headers['Authorization'] = `Bearer ${token}`;
                    } else {
                        throw new Error('Autenticação necessária. Por favor, faça login primeiro.');
                    }
                }

                if (body) {
                    options.body = JSON.stringify(body);
                }

                const response = await fetch(`${window.API_URL}${endpoint}`, options);
                const data = await response.json();
                
                if (!response.ok) {
                    if (response.status === 401 && requiresAuth) {
                        localStorage.removeItem('auth_token');
                        throw new Error('Sessão expirada. Por favor, faça login novamente.');
                    }
                    throw new Error(data.message || `Erro ${response.status}`);
                }
                
                return data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        };

        window.delay = ms => new Promise(res => setTimeout(res, ms));

        window.getStatusClass = function(status) {
            const classes = {
                'pendente_revisao': 'bg-orange-100 text-orange-800',
                'aprovado': 'bg-green-100 text-green-800',
                'reprovado': 'bg-red-100 text-red-800',
                'publicado': 'bg-blue-100 text-blue-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        };

        window.getStatusText = function(status) {
            const texts = {
                'pendente_revisao': 'Pendente de Revisão',
                'aprovado': 'Aprovado',
                'reprovado': 'Reprovado',
                'publicado': 'Publicado'
            };
            return texts[status] || status;
        };

        window.login = async function(usuario, senha) {
            try {
                const response = await window.apiCall('/user', 'POST', { usuario, senha });
                if (response.token) {
                    localStorage.setItem('auth_token', response.token);
                    return true;
                }
                return false;
            } catch (error) {
                console.error('Erro ao fazer login:', error);
                throw error;
            }
        };
    </script>
    <script>
        // Inicializar ícones Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Configuração da API
        window.API_BASE_URL = '{{ config('app.url', 'http://localhost:8000') }}';
        window.API_URL = window.API_BASE_URL + '/api';
        
        // CSRF Token para requisições
        window.csrfToken = '{{ csrf_token() }}';
    </script>
    
    @stack('scripts')
</body>
</html>

