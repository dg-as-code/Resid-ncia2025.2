require('./bootstrap');

/**
 * Sistema de Agentes de IA - JavaScript Principal
 * 
 * Funções utilitárias compartilhadas entre todas as views
 */

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

        // Adicionar token de autenticação se necessário
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

// Função para delay
window.delay = ms => new Promise(res => setTimeout(res, ms));

// Função para formatar status
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

// Função para login
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

// Inicializar ícones Lucide quando disponível
if (typeof lucide !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
}
