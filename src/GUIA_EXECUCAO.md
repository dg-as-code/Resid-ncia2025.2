# 🚀 Guia de Execução do Projeto

Este guia explica como configurar e executar o sistema de agentes de IA para análise de mercado financeiro.

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- **Docker** e **Docker Compose** (recomendado)
- **PHP 8.1+** (se executar sem Docker)
- **Composer** (se executar sem Docker)
- **MySQL 5.7+** ou **MariaDB**
- **Python 3.8+** (para scripts LLM)
- **Node.js** (opcional, para assets frontend)

## 🐳 Opção 1: Execução com Docker (Recomendado)

### 1. Clonar e Configurar

```bash
# Clone o repositório (se ainda não tiver)
git clone <url-do-repositorio>
cd residencia_2025_02_API_COM_LARAVEL

# Entre no diretório src
cd src
```

### 2. Configurar Variáveis de Ambiente

```bash
# Copie o arquivo .env.example para .env
cp .env.example .env

# Edite o arquivo .env com suas configurações
nano .env  # ou use seu editor preferido
```

### 3. Configurações Importantes no .env

```env
# Aplicação
APP_NAME="Sistema de Agentes de IA"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=user
DB_PASSWORD=password

# Configurações dos Agentes
YAHOO_FINANCE_BASE_URL=https://query1.finance.yahoo.com/v8/finance/chart
YAHOO_FINANCE_TIMEOUT=10
YAHOO_FINANCE_RATE_LIMIT=5

NEWS_API_KEY=sua_chave_aqui
NEWS_API_BASE_URL=https://newsapi.org/v2
NEWS_API_TIMEOUT=10

LLM_PROVIDER=python
PYTHON_PATH=python3
LLM_SCRIPT_PATH=llm/scripts/run_llm.py
LLM_TIMEOUT=60

# Notificações
REVIEWER_EMAIL=revisor@example.com
ADMIN_EMAIL=admin@example.com

# Logs
LOG_CHANNEL=daily
LOG_LEVEL=info
```

### 4. Executar com Docker

```bash
# Volte para a raiz do projeto
cd ..

# Inicie os containers
docker-compose up -d

# Aguarde os containers iniciarem (alguns segundos)
docker-compose ps
```

### 5. Instalar Dependências e Configurar

```bash
# Entre no container da aplicação
docker-compose exec app bash

# Dentro do container, execute:
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 6. Verificar Instalação

```bash
# Verificar se a API está respondendo
curl http://localhost/api/agents/status

# Ou acesse no navegador
# http://localhost
```

## 💻 Opção 2: Execução Local (Sem Docker)

### 1. Configurar Ambiente

```bash
cd src

# Instalar dependências PHP
composer install

# Instalar dependências Python
pip install -r requirements.txt

# Gerar chave da aplicação
php artisan key:generate

# Configurar .env
cp .env.example .env
# Edite o .env com suas configurações
```

### 2. Configurar Banco de Dados

```bash
# Criar banco de dados manualmente
mysql -u root -p
CREATE DATABASE db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Executar migrações
php artisan migrate

# Popular banco de dados
php artisan db:seed
```

### 3. Iniciar Servidor

```bash
# Iniciar servidor de desenvolvimento
php artisan serve

# A API estará disponível em:
# http://localhost:8000
```

## 🔧 Configuração Inicial

### 1. Popular Ações Monitoradas

```bash
# Executar seeder de ações
php artisan db:seed --class=StockSymbolSeeder

# Ou através do Docker
docker-compose exec app php artisan db:seed --class=StockSymbolSeeder
```

### 2. Verificar Status

```bash
# Verificar status dos agentes
curl http://localhost/api/agents/status

# Ou via navegador
# http://localhost/api/agents/status
```

## 🤖 Executando os Agentes

### Execução Manual via Artisan

```bash
# Agente Júlia - Coleta dados financeiros
php artisan agent:julia:fetch

# Coletar dados de uma ação específica
php artisan agent:julia:fetch --symbol=PETR4

# Coletar dados de todas as ações padrão
php artisan agent:julia:fetch --all

# Agente Pedro - Análise de sentimento
php artisan agent:pedro:analyze

# Agente Key - Gerar matérias financeiras
php artisan agent:key:compose

# Agente PublishNotify - Notificar revisores
php artisan agent:publish:notify

# Agente Cleanup - Limpeza e manutenção
php artisan agent:cleanup
```

### Execução via API

```bash
# Status dos agentes
curl http://localhost/api/agents/status

# Executar Agente Júlia
curl -X POST http://localhost/api/agents/julia \
  -H "Content-Type: application/json" \
  -d '{"symbol": "PETR4"}'

# Executar Agente Pedro
curl -X POST http://localhost/api/agents/pedro

# Executar Agente Key
curl -X POST http://localhost/api/agents/key

# Executar Agente PublishNotify
curl -X POST http://localhost/api/agents/publish-notify

# Executar Agente Cleanup
curl -X POST http://localhost/api/agents/cleanup
```

### Execução Automática (Agendada)

Os agentes são executados automaticamente via agendamento do Laravel:

```bash
# Verificar agendamento
php artisan schedule:list

# Executar agendamento manualmente (para testes)
php artisan schedule:run

# Iniciar worker de agendamento (produção)
php artisan schedule:work
```

## 📊 Testando a API

### 1. Autenticação

```bash
# Obter token JWT
curl -X POST http://localhost/api/user \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@example.com",
    "password": "senha123"
  }'
```

### 2. Consultar Ações Monitoradas

```bash
# Listar todas as ações
curl http://localhost/api/stock-symbols

# Visualizar ação específica
curl http://localhost/api/stock-symbols/1
```

### 3. Consultar Dados Financeiros

```bash
# Listar dados financeiros
curl http://localhost/api/financial-data

# Último dado de uma ação
curl http://localhost/api/financial-data/symbol/PETR4/latest
```

### 4. Consultar Análises de Sentimento

```bash
# Listar análises
curl http://localhost/api/sentiment-analysis

# Última análise de uma ação
curl http://localhost/api/sentiment-analysis/symbol/PETR4/latest
```

### 5. Consultar Artigos

```bash
# Listar artigos
curl http://localhost/api/articles

# Visualizar artigo específico
curl http://localhost/api/articles/1
```

### 6. Aprovar/Reprovar Artigos

```bash
# Aprovar artigo (requer autenticação)
curl -X POST http://localhost/api/articles/1/approve \
  -H "Authorization: Bearer SEU_TOKEN_JWT"

# Reprovar artigo
curl -X POST http://localhost/api/articles/1/reject \
  -H "Authorization: Bearer SEU_TOKEN_JWT" \
  -H "Content-Type: application/json" \
  -d '{"motivo_reprovacao": "Conteúdo não adequado"}'

# Publicar artigo
curl -X POST http://localhost/api/articles/1/publish \
  -H "Authorization: Bearer SEU_TOKEN_JWT"
```

## 🔍 Monitoramento e Logs

### Visualizar Logs

```bash
# Log principal
tail -f storage/logs/laravel.log

# Log do Agente Júlia
tail -f storage/logs/agent_julia.log

# Log do Agente Pedro
tail -f storage/logs/agent_pedro.log

# Log do Agente Key
tail -f storage/logs/agent_key.log

# Log geral dos agentes
tail -f storage/logs/agents.log
```

### Verificar Status

```bash
# Status dos agentes via API
curl http://localhost/api/agents/status

# Verificar filas (se usando)
php artisan queue:work
```

## 🛠️ Comandos Úteis

### Desenvolvimento

```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recriar banco de dados
php artisan migrate:fresh --seed

# Executar testes
php artisan test
```

### Produção

```bash
# Otimizar aplicação
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Executar agendamento
php artisan schedule:work

# Processar filas
php artisan queue:work
```

## 🐛 Troubleshooting

### Problemas Comuns

#### 1. Erro de Conexão com Banco de Dados

```bash
# Verificar configurações no .env
# Verificar se o container do banco está rodando
docker-compose ps

# Testar conexão
php artisan tinker
>>> DB::connection()->getPdo();
```

#### 2. Erro ao Executar Scripts Python

```bash
# Verificar se Python está instalado
python3 --version

# Instalar dependências Python
pip install -r requirements.txt

# Verificar permissões do script
chmod +x llm/scripts/run_llm.py
```

#### 3. Erro de Permissões

```bash
# Dar permissões ao storage
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### 4. Erro de Rate Limiting

```bash
# Limpar cache de rate limiting
php artisan cache:clear
```

## 📝 Próximos Passos

1. **Configurar APIs Externas**:
   - Obter chave da News API: https://newsapi.org/
   - Configurar OpenAI/Anthropic (opcional)

2. **Configurar Notificações**:
   - Configurar SMTP no `.env`
   - Adicionar emails de revisores

3. **Configurar Agendamento**:
   - Verificar `app/Console/Kernel.php`
   - Configurar cron job em produção

4. **Monitoramento**:
   - Configurar logs
   - Configurar alertas
   - Monitorar performance

## 📚 Documentação Adicional

- **API Endpoints**: Ver `routes/api.php`
- **Agentes de IA**: Ver `app/Console/Commands/`
- **Models**: Ver `app/Models/`
- **Services**: Ver `app/Services/`

## 🆘 Suporte

Para dúvidas ou problemas:
1. Verifique os logs em `storage/logs/`
2. Consulte a documentação do Laravel: https://laravel.com/docs
3. Verifique as configurações no `.env`

---

**Boa sorte com o projeto! 🚀**

