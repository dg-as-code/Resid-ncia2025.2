# Public Directory

Este diretório é o ponto de entrada público da aplicação Laravel. Todos os arquivos aqui são acessíveis diretamente via URL.

## Estrutura

```
public/
├── index.php        # Ponto de entrada principal da aplicação
├── .htaccess       # Configurações do Apache (rewrite rules)
├── robots.txt      # Configuração para crawlers de busca
├── favicon.ico     # Ícone do site
└── README.md       # Este arquivo
```

## Arquivos

### `index.php`

Ponto de entrada principal da aplicação. Todas as requisições HTTP são processadas através deste arquivo.

**Processo:**
1. Verifica modo de manutenção
2. Carrega autoloader do Composer
3. Inicializa aplicação Laravel
4. Processa requisição via HTTP Kernel
5. Envia resposta ao cliente

**Endpoints da API:**
- `/api/agents/*` - Execução e status dos agentes
- `/api/stock-symbols/*` - Gerenciamento de ações
- `/api/financial-data/*` - Dados financeiros
- `/api/sentiment-analysis/*` - Análises de sentimento
- `/api/articles/*` - Artigos/matérias geradas

### `.htaccess`

Configurações do Apache para:
- Rewrite rules (redirecionamento para `index.php`)
- Headers de autorização
- Segurança (bloqueio de arquivos sensíveis)

### `robots.txt`

Configuração para crawlers de mecanismos de busca:
- Permite acesso público
- Bloqueia endpoints de API (`/api/`)
- Protege áreas administrativas (`/admin/`)
- Bloqueia arquivos sensíveis

### `favicon.ico`

Ícone do site exibido no navegador.

## Segurança

### Arquivos Bloqueados

O `.htaccess` bloqueia acesso direto a:
- Arquivos `.env` (variáveis de ambiente)
- Arquivos `.log` (logs do sistema)
- Arquivos `.md` (documentação)

### Endpoints Protegidos

O `robots.txt` bloqueia acesso de crawlers a:
- `/api/*` - Endpoints da API
- `/admin/*` - Área administrativa
- `/storage/*` - Arquivos armazenados

## Desenvolvimento

### Local

```bash
php artisan serve
```

Acesse: `http://localhost:8000`

### Produção

Configure o servidor web (Apache/Nginx) para:
- Apontar document root para `public/`
- Processar requisições via `index.php`
- Aplicar regras do `.htaccess`

## Notas

- **Não modifique** a estrutura básica do `index.php` sem entender o impacto
- **Mantenha** o `.htaccess` atualizado para segurança
- **Atualize** o `robots.txt` conforme necessário para SEO
- **Não coloque** arquivos sensíveis neste diretório

## Sistema de Agentes de IA

Esta aplicação utiliza agentes de IA para:
- **Júlia**: Coleta dados financeiros (Yahoo Finance)
- **Pedro**: Análise de sentimento de mercado e mídia
- **Key**: Geração de matérias financeiras usando LLM
- **PublishNotify**: Notificações para revisão humana
- **Cleanup**: Limpeza e manutenção do sistema

Todos os endpoints dos agentes estão protegidos por autenticação e rate limiting.

