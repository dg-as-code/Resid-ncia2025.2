# 📊 Sistema de Análise Financeira com Agentes de IA

**Projeto de Mentoria - API 2025.2**

Sistema automatizado de análise financeira que utiliza **agentes de inteligência artificial** para coletar dados, analisar sentimentos de mercado e gerar matérias jornalísticas sobre ações da bolsa de valores brasileira (B3), com revisão humana obrigatória antes da publicação.

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura do Sistema](#arquitetura-do-sistema)
3. [Agentes de IA](#agentes-de-ia)
4. [Tecnologias Utilizadas](#tecnologias-utilizadas)
5. [Instalação e Configuração](#instalação-e-configuração)
6. [Uso e Execução](#uso-e-execução)
7. [APIs e Endpoints](#apis-e-endpoints)
8. [Estrutura do Projeto](#estrutura-do-projeto)
9. [Fluxo Completo do Sistema](#fluxo-completo-do-sistema)
10. [Documentação Adicional](#documentação-adicional)
11. [Troubleshooting](#troubleshooting)
12. [Desenvolvimento e Contribuição](#desenvolvimento-e-contribuição)

---

## 🎯 Visão Geral

### Proposta

Criar um grupo de agentes de inteligência artificial para analisar ações e gerar conteúdos recomendando (ou não) a compra, com base em dados reais e percepção de mercado.

### Objetivo

Produzir **matérias financeiras claras, confiáveis e baseadas em dados**, combinando:
- ✅ Coleta automática de dados financeiros
- ✅ Análise de sentimento do mercado e mídia
- ✅ Geração de conteúdo com IA (LLM)
- ✅ **Revisão humana obrigatória** antes da publicação

### Principais Funcionalidades

- **Coleta Automática de Dados**: Agente Júlia coleta dados financeiros atualizados via Google Gemini API
- **Análise de Sentimento**: Agente Pedro analisa notícias e mídia sobre empresas usando News API
- **Geração de Conteúdo**: Agente Key gera matérias jornalísticas profissionais usando LLM (Gemini)
- **Revisão Humana**: Sistema de aprovação/reprovação por revisores humanos
- **Publicação**: Publicação de matérias aprovadas
- **Orquestração**: Fluxo completo automatizado (Júlia → Pedro → Key → Revisão)
- **Manutenção**: Limpeza e otimização automática do sistema

---

## 🏗️ Arquitetura do Sistema

### Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (Blade/HTML/JS)                     │
│  - Dashboard                                                    │
│  - Orquestração de Agentes                                      │
│  - Revisão de Artigos                                           │
│  - Visualização de Dados                                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ HTTP/REST
                             │
┌────────────────────────────▼─────────────────────────────────────┐
│                    LARAVEL APPLICATION (PHP)                     │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │  API Layer (Controllers)                                  │   │
│  │  - OrchestrationController (Fluxo completo)               │   │
│  │  - AgentController (Execução individual)                  │   │
│  │  - ArticleController (Gerenciamento de artigos)           │   │
│  │  - FinancialDataController                                │   │
│  │  - SentimentAnalysisController                            │   │
│  │  - StockSymbolController                                  │   │
│  └──────────────────────────┬────────────────────────────────┘   │
│                             │                                    │
│  ┌──────────────────────────▼────────────────────────────────┐   │
│  │  Business Logic Layer                                     │   │
│  │  - Commands (Agentes Artisan)                             │   │
│  │  - Jobs (Processamento assíncrono)                        │   │
│  │  - Services (Integração com APIs)                         │   │
│  └──────────────────────────┬────────────────────────────────┘   │
│                             │                                    │
│  ┌──────────────────────────▼────────────────────────────────┐   │
│  │  Data Access Layer (Eloquent ORM)                         │   │
│  │  - Models                                                 │   │
│  │  - Migrations                                             │   │
│  │  - Factories & Seeders                                    │   │
│  └──────────────────────────┬────────────────────────────────┘   │
└─────────────────────────────┼────────────────────────────────────┘
                              │
                              │ Eloquent ORM
                              │
┌─────────────────────────────▼────────────────────────────────────┐
│                      MYSQL DATABASE                              │
│  - stock_symbols (Ações monitoradas)                             │
│  - financial_data (Dados coletados por Júlia)                    │
│  - sentiment_analysis (Análises do Pedro)                        │
│  - articles (Matérias geradas por Key)                           │
│  - analyses (Análises completas via Jobs)                        │
│  - users (Revisores)                                             │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                            │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Google Gemini API (Agente Júlia e Key)                  │   │
│  │  - Dados financeiros                                     │   │
│  │  - Geração de artigos                                    │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  News API (Agente Pedro)                                 │   │
│  │  - Notícias e análise de sentimento                      │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Python LLM Service (Fallback)                           │   │
│  │  - Geração de conteúdo (alternativa)                     │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes Principais

1. **API Layer**: Controllers REST para gerenciar requisições HTTP
2. **Business Logic Layer**: Commands, Jobs e Services para lógica de negócio
3. **Data Access Layer**: Models Eloquent para acesso ao banco de dados
4. **External Services**: Integração com APIs externas (Gemini, News API)

---

## 🤖 Agentes de IA

O sistema possui **5 agentes especializados** que trabalham em conjunto:

### 1. 🧮 Agente Júlia - Coleta de Dados Financeiros

**Responsabilidade**: Coletar dados financeiros atualizados de mercado

**Como Funciona**:
- Busca ações monitoradas no banco de dados
- Para cada ação, consulta Google Gemini API para obter dados financeiros
- Processa e normaliza os dados (preço, volume, market cap, P/E ratio, etc.)
- Salva em `financial_data` com timestamp e dados brutos

**Frequência**: A cada 10 minutos (ou sob demanda)

**Comando Artisan**: `php artisan agent:julia:fetch --symbol=Petrobras`

**API Endpoint**: `POST /api/agents/julia`

**Serviço**: `YahooFinanceService` (usa Gemini API)

**Dados Coletados**:
- Preço atual e variação
- Volume de negociação
- Market cap
- P/E ratio
- Dividend yield
- Máximas e mínimas de 52 semanas
- Dados brutos completos (raw_data)

---

### 2. 📰 Agente Pedro - Análise de Sentimento

**Responsabilidade**: Analisar o que o mercado e a mídia estão dizendo sobre a empresa

**Como Funciona**:
- Busca ações monitoradas
- Para cada ação, consulta News API para obter notícias recentes
- Analisa sentimento das notícias (positivo, negativo, neutro)
- Identifica tópicos em destaque (trending topics)
- Gera análise enriquecida com métricas de mercado, marca e insights estratégicos
- Salva em `sentiment_analysis` com todos os dados enriquecidos

**Frequência**: A cada hora (ou sob demanda)

**Comando Artisan**: `php artisan agent:pedro:analyze`

**API Endpoint**: `POST /api/agents/pedro`

**Serviço**: `NewsAnalysisService`

**Dados Gerados**:
- Sentimento geral (positive/negative/neutral)
- Score de sentimento (-1 a 1)
- Contagem de notícias por sentimento
- Tópicos em destaque
- Análise de mercado e macroeconomia
- Métricas de marca e percepção
- Insights estratégicos
- Dados digitais e comportamentais
- Alertas de risco e oportunidades

---

### 3. ✍️ Agente Key - Geração de Conteúdo

**Responsabilidade**: Gerar rascunho de matéria financeira baseado nos dados dos outros agentes

**Como Funciona**:
- Busca ações com dados financeiros e análise de sentimento recentes (últimas 24h)
- Consolida dados de Júlia (financeiros) e Pedro (sentimento)
- Chama Google Gemini API para gerar artigo jornalístico profissional
- Extrai recomendação do conteúdo gerado
- Salva em `articles` com status `pendente_revisao`

**Frequência**: A cada 30 minutos (ou sob demanda)

**Comando Artisan**: `php artisan agent:key:compose`

**API Endpoint**: `POST /api/agents/key`

**Serviço**: `LLMService` / `GeminiResponseService`

**Dados Gerados**:
- Título da matéria
- Conteúdo HTML formatado
- Recomendação (comprar/manter/vender)
- Metadata (agente, fluxo, formato)

---

### 4. 📧 Agente PublishNotify - Notificação

**Responsabilidade**: Verificar matérias pendentes e notificar revisores humanos

**Como Funciona**:
- Busca artigos com status `pendente_revisao` e não notificados
- Prepara email com lista de artigos pendentes
- Envia email para revisores configurados
- Marca artigos como notificados (`notified_at`)

**Frequência**: A cada 15 minutos (ou sob demanda)

**Comando Artisan**: `php artisan agent:publish:notify`

**API Endpoint**: `POST /api/agents/publish-notify`

**Serviço**: Mail (Laravel)

**Configuração**: `REVIEWER_EMAIL` no `.env`

---

### 5. 🧹 Agente Cleanup - Limpeza e Manutenção

**Responsabilidade**: Limpar arquivos temporários, caches antigos e manter o sistema organizado

**Como Funciona**:
- Limpa logs antigos (>30 dias)
- Limpa arquivos temporários (>30 dias)
- Limpa caches antigos
- Limpa dados financeiros antigos (>90 dias, opcional)
- Limpa análises de sentimento antigas (>90 dias, opcional)

**Frequência**: Diário às 03:00 (ou sob demanda)

**Comando Artisan**: `php artisan agent:cleanup`

**API Endpoint**: `POST /api/agents/cleanup`

---

## 🛠️ Tecnologias Utilizadas

### Backend

- **Laravel 8+**: Framework PHP para orquestração e API REST
- **PHP 8.0+**: Linguagem de programação
- **MySQL 5.7+**: Banco de dados relacional
- **Composer**: Gerenciador de dependências PHP
- **Eloquent ORM**: Mapeamento objeto-relacional

### Frontend

- **Blade Templates**: Sistema de templates do Laravel
- **HTML5, CSS3, JavaScript**: Interface web
- **Tailwind CSS**: Framework CSS utilitário
- **Lucide Icons**: Biblioteca de ícones

### Serviços Externos

- **Google Gemini API**: 
  - Coleta de dados financeiros (Agente Júlia)
  - Geração de artigos (Agente Key)
- **News API**: 
  - Busca de notícias (Agente Pedro)
- **Python 3.9+**: 
  - Serviço LLM alternativo (fallback)

### Infraestrutura

- **Docker**: Containerização da aplicação
- **Docker Compose**: Orquestração de containers
- **Nginx**: Servidor web
- **PHP-FPM**: Processador PHP
- **MySQL**: Banco de dados

### Ferramentas de Desenvolvimento

- **Git**: Controle de versão
- **PHPUnit**: Testes unitários e de integração
- **Artisan**: CLI do Laravel
- **Laravel Queue**: Processamento assíncrono (Jobs)

---

## 🚀 Instalação e Configuração

### Pré-requisitos

- ✅ Docker Desktop instalado e rodando
- ✅ Portas 80, 443, 3306 e 8000 disponíveis
- ✅ Git instalado (opcional)
- ✅ 4GB+ de RAM disponível

### Opção 1: Script Automatizado (Recomendado)

#### Windows:
```bash
inicializar.bat
```

#### Linux/Mac:
```bash
chmod +x inicializar.sh
./inicializar.sh
```

### Opção 2: Instalação Manual

#### 1. Clonar o Repositório
```bash
git clone <repository-url>
cd residencia_2025_02_API_COM_LARAVEL
```

#### 2. Levantar o Ambiente Docker
```bash
docker-compose up --build -d
```

#### 3. Aguardar Banco de Dados (30 segundos)
```bash
# Windows PowerShell
Start-Sleep -Seconds 30

# Linux/Mac
sleep 30
```

#### 4. Acessar o Container da Aplicação
```bash
docker exec -it laravel_app bash
```

#### 5. Dentro do Container - Configurar Aplicação
```bash
# Instalar dependências PHP
composer install

# Gerar chave da aplicação
php artisan key:generate

# Executar migrations
php artisan migrate

# Popular banco com dados iniciais
php artisan db:seed

# Configurar permissões
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 6. Sair do Container
```bash
exit
```

### Configuração de Variáveis de Ambiente

Edite o arquivo `src/.env` e configure:

```env
# Aplicação
APP_NAME="Sistema de Agentes de IA"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=user
DB_PASSWORD=password

# Google Gemini API (Agente Júlia e Key)
GEMINI_API_KEY=sua-chave-gemini-aqui
GEMINI_MODEL=gemini-1.5-flash
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_TIMEOUT=60

# News API (Agente Pedro)
NEWS_API_KEY=sua-chave-news-api-aqui
NEWS_API_BASE_URL=https://newsapi.org/v2
NEWS_API_TIMEOUT=10

# Email (Agente PublishNotify)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Revisores (Agente PublishNotify)
REVIEWER_EMAIL=revisor@example.com

# JWT (Autenticação)
JWT_KEY=sua-chave-jwt-secreta-aqui

# LLM Service (Fallback)
LLM_PROVIDER=gemini
PYTHON_PATH=python3
LLM_SCRIPT_PATH=llm/scripts/run_llm.py
LLM_TIMEOUT=60
```

### Obter API Keys

#### Google Gemini API
1. Acesse: https://makersuite.google.com/app/apikey
2. Crie uma nova API key
3. Copie e cole no `.env` como `GEMINI_API_KEY`

#### News API
1. Acesse: https://newsapi.org/register
2. Crie uma conta gratuita
3. Copie sua API key
4. Cole no `.env` como `NEWS_API_KEY`

### Verificar Instalação

```bash
# Verificar status dos containers
docker-compose ps

# Verificar logs
docker-compose logs app

# Acessar aplicação
# http://localhost:8000
```

---

## 📖 Uso e Execução

### Execução via Interface Web

1. Acesse: `http://localhost:8000`
2. Navegue até **"Orquestração de Agentes"**
3. Digite o nome da empresa (ex: "Petrobras")
4. Clique em **"Iniciar Orquestração"**
5. Aguarde o fluxo completo: Júlia → Pedro → Key → Revisão

### Execução via API

#### Orquestração Completa (Recomendado)
```bash
curl -X POST http://localhost:8000/api/orchestrate \
  -H "Content-Type: application/json" \
  -d '{"company_name": "Petrobras"}'
```

#### Execução Individual de Agentes

**Agente Júlia (Coleta de Dados)**:
```bash
curl -X POST http://localhost:8000/api/agents/julia
```

**Agente Pedro (Análise de Sentimento)**:
```bash
curl -X POST http://localhost:8000/api/agents/pedro
```

**Agente Key (Geração de Conteúdo)**:
```bash
curl -X POST http://localhost:8000/api/agents/key
```

**Agente PublishNotify (Notificação)**:
```bash
curl -X POST http://localhost:8000/api/agents/publish-notify
```

**Agente Cleanup (Limpeza)**:
```bash
curl -X POST http://localhost:8000/api/agents/cleanup
```

### Execução via Artisan (Dentro do Container)

```bash
# Acessar container
docker exec -it laravel_app bash

# Executar agentes
php artisan agent:julia:fetch --symbol=Petrobras
php artisan agent:pedro:analyze
php artisan agent:key:compose
php artisan agent:publish:notify
php artisan agent:cleanup
```

### Execução Automática (Agendamento)

Os agentes podem ser agendados via Laravel Scheduler. Configure no `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Agente Júlia: A cada 10 minutos
    $schedule->command('agent:julia:fetch')->everyTenMinutes();
    
    // Agente Pedro: A cada hora
    $schedule->command('agent:pedro:analyze')->hourly();
    
    // Agente Key: A cada 30 minutos
    $schedule->command('agent:key:compose')->everyThirtyMinutes();
    
    // Agente PublishNotify: A cada 15 minutos
    $schedule->command('agent:publish:notify')->everyFifteenMinutes();
    
    // Agente Cleanup: Diário às 03:00
    $schedule->command('agent:cleanup')->dailyAt('03:00');
}
```

E configure o cron no servidor:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Processamento Assíncrono (Jobs)

O sistema também suporta processamento assíncrono via Laravel Jobs:

```bash
# Solicitar análise completa (via Jobs)
curl -X POST http://localhost:8000/api/analyses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_JWT" \
  -d '{
    "symbol": "PETR4",
    "company_name": "Petrobras"
  }'
```

Isso cria uma cadeia de jobs:
1. `FetchFinancialDataJob` (Júlia)
2. `AnalyzeMarketSentimentJob` (Pedro)
3. `DraftArticleJob` (Key)
4. `NotifyReviewerJob` (PublishNotify)

---

## 🔌 APIs e Endpoints

### Base URL
```
http://localhost:8000/api
```

### Autenticação

#### POST `/api/user`
Autenticação JWT

**Request**:
```json
{
  "email": "usuario@example.com",
  "password": "senha123"
}
```

**Response** (200):
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Usuário",
    "email": "usuario@example.com"
  }
}
```

**Uso**:
```bash
curl -X POST http://localhost:8000/api/user \
  -H "Content-Type: application/json" \
  -d '{"email": "usuario@example.com", "password": "senha123"}'
```

---

### Agentes

#### GET `/api/agents/status`
Status de todos os agentes

**Response** (200):
```json
{
  "julia": {
    "last_run": "2025-12-03 10:00:00",
    "status": "success",
    "records_created": 5
  },
  "pedro": { ... },
  "key": { ... }
}
```

#### POST `/api/agents/julia`
Executa Agente Júlia

**Response** (200):
```json
{
  "success": true,
  "message": "Agente Júlia executado com sucesso",
  "data": {
    "records_created": 5,
    "symbols_processed": ["PETR4", "VALE3", ...]
  }
}
```

#### POST `/api/agents/pedro`
Executa Agente Pedro (Análise de Sentimento de Mercado)

**Response** (200):
```json
{
  "success": true,
  "message": "Agente Pedro executado com sucesso. Análise de sentimento de mercado e opiniões da mídia concluída.",
  "output": "Análise de sentimento concluída para 3 ações...",
  "data": {
    "sentiment_analysis": {
      "id": 42,
      "symbol": "PETR4",
      "sentiment": "positive",
      "sentiment_score": 0.65,
      "news_count": 15,
      "positive_count": 9,
      "negative_count": 3,
      "neutral_count": 3,
      "trending_topics": "expansão offshore, produção de petróleo, investimentos em energia renovável, resultados trimestrais",
      "news_sources": ["Reuters", "Bloomberg", "Valor Econômico", "Infomoney"],
      "analyzed_at": "2025-12-03T10:30:00.000000Z",
      "market_analysis": {
        "overall_trend": "alta",
        "market_sentiment": "otimista",
        "key_drivers": [
          "Aumento na produção de petróleo",
          "Investimentos em energia renovável",
          "Expectativas positivas para o próximo trimestre"
        ],
        "market_volatility": "moderada"
      },
      "macroeconomic_analysis": {
        "economic_outlook": "favorável",
        "sector_performance": "superior à média",
        "regulatory_environment": "estável"
      },
      "key_insights": [
        "Empresa demonstra forte crescimento em produção",
        "Investimentos em sustentabilidade bem recebidos pelo mercado",
        "Expectativas positivas para próximos resultados"
      ],
      "recommendation": "manter",
      "total_mentions": 1250,
      "mentions_peak": {
        "date": "2025-12-02",
        "count": 180
      },
      "sentiment_breakdown": {
        "social_media": {
          "positive": 65,
          "negative": 20,
          "neutral": 15
        },
        "news_media": {
          "positive": 60,
          "negative": 20,
          "neutral": 20
        }
      },
      "engagement_metrics": {
        "total_engagement": 8500,
        "likes": 3200,
        "shares": 1800,
        "comments": 3500
      },
      "engagement_score": 0.72,
      "investor_confidence": {
        "level": "alto",
        "score": 0.78,
        "trend": "crescimento"
      },
      "confidence_score": 0.78,
      "brand_perception": {
        "overall": "positiva",
        "trust_score": 0.75,
        "innovation_score": 0.68,
        "sustainability_score": 0.82
      },
      "main_themes": [
        "Expansão de operações",
        "Sustentabilidade",
        "Resultados financeiros",
        "Inovação tecnológica"
      ],
      "emotions_analysis": {
        "joy": 0.45,
        "trust": 0.60,
        "fear": 0.15,
        "surprise": 0.25
      },
      "actionable_insights": [
        "Aumentar comunicação sobre investimentos em sustentabilidade",
        "Manter foco em resultados operacionais",
        "Fortalecer presença digital"
      ],
      "improvement_opportunities": [
        "Ampliar comunicação sobre estratégia de longo prazo",
        "Melhorar engajamento em redes sociais"
      ],
      "risk_alerts": [
        "Volatilidade do preço do petróleo",
        "Mudanças regulatórias no setor"
      ],
      "strategic_analysis": {
        "competitive_position": "forte",
        "market_share_trend": "crescimento",
        "strategic_initiatives": [
          "Expansão em energia renovável",
          "Modernização de infraestrutura"
        ]
      },
      "raw_data": {
        "articles": [
          {
            "title": "Petrobras anuncia aumento na produção",
            "source": "Reuters",
            "published_at": "2025-12-02T08:00:00Z",
            "sentiment": "positive"
          }
        ],
        "_analysis": {
          "digital_data": {
            "mentions": 1250,
            "engagement": 8500,
            "reach": 150000
          },
          "behavioral_data": {
            "purchase_intent": 0.65,
            "complaints": 0.12,
            "feedback_score": 0.78
          }
        }
      }
    },
    "records_created": 1,
    "symbols_processed": ["PETR4"]
  }
}
```

#### POST `/api/agents/key`
Executa Agente Key (Geração de Matéria Jornalística)

**Response** (200):
```json
{
  "success": true,
  "message": "Agente Key executado com sucesso. Matéria jornalística gerada pela redatora veterana.",
  "output": "Matéria gerada para 2 ações...",
  "data": {
    "article": {
      "id": 123,
      "symbol": "PETR4",
      "title": "Petrobras: Análise Aponta Sentimento Positivo do Mercado com Foco em Expansão e Sustentabilidade",
      "content": "<h1>Petrobras: Análise Aponta Sentimento Positivo do Mercado com Foco em Expansão e Sustentabilidade</h1>\n\n<p><strong>Por Agente Key - Redatora Veterana</strong><br>\n<em>Publicado em: 03 de dezembro de 2025</em></p>\n\n<h2>Resumo Executivo</h2>\n\n<p>A <strong>Petrobras (PETR4)</strong> apresenta um cenário de <strong>sentimento positivo</strong> no mercado, com score de <strong>0.65</strong>, baseado na análise de <strong>15 notícias</strong> coletadas nas últimas 24 horas. A empresa demonstra forte crescimento em produção e investimentos estratégicos em energia renovável, gerando expectativas otimistas entre investidores.</p>\n\n<h2>Dados Financeiros</h2>\n\n<p>Com base nos dados coletados pelo <strong>Agente Júlia</strong>:</p>\n\n<ul>\n  <li><strong>Preço Atual:</strong> R$ 38,50</li>\n  <li><strong>Variação:</strong> +2,3% (R$ 0,87)</li>\n  <li><strong>Volume Negociado:</strong> 45.230.000 ações</li>\n  <li><strong>Capitalização de Mercado:</strong> R$ 520,8 bilhões</li>\n  <li><strong>P/L:</strong> 8,5</li>\n  <li><strong>Dividend Yield:</strong> 12,5%</li>\n  <li><strong>Máxima 52 semanas:</strong> R$ 42,30</li>\n  <li><strong>Mínima 52 semanas:</strong> R$ 28,10</li>\n</ul>\n\n<h2>Análise de Sentimento do Mercado</h2>\n\n<p>O <strong>Agente Pedro</strong> identificou um sentimento predominantemente <strong>positivo</strong> em relação à Petrobras, com <strong>9 notícias positivas</strong>, <strong>3 negativas</strong> e <strong>3 neutras</strong>. Os principais tópicos em destaque incluem:</p>\n\n<ul>\n  <li>Expansão offshore</li>\n  <li>Produção de petróleo</li>\n  <li>Investimentos em energia renovável</li>\n  <li>Resultados trimestrais</li>\n</ul>\n\n<h3>Análise de Mercado</h3>\n\n<p>O mercado demonstra uma <strong>tendência de alta</strong> com sentimento <strong>otimista</strong>. Os principais drivers incluem:</p>\n\n<ul>\n  <li>Aumento na produção de petróleo</li>\n  <li>Investimentos em energia renovável</li>\n  <li>Expectativas positivas para o próximo trimestre</li>\n</ul>\n\n<h3>Métricas de Engajamento</h3>\n\n<p>A empresa registrou <strong>1.250 menções</strong> nas últimas 24 horas, com pico de <strong>180 menções</strong> em 02 de dezembro. O engajamento total alcançou <strong>8.500 interações</strong>, distribuídas em:</p>\n\n<ul>\n  <li><strong>3.200 curtidas</strong></li>\n  <li><strong>1.800 compartilhamentos</strong></li>\n  <li><strong>3.500 comentários</strong></li>\n</ul>\n\n<h3>Confiança do Investidor</h3>\n\n<p>O nível de confiança dos investidores está <strong>alto</strong>, com score de <strong>0.78</strong>, demonstrando uma tendência de <strong>crescimento</strong>. A percepção da marca é <strong>positiva</strong>, com destaque para:</p>\n\n<ul>\n  <li><strong>Score de Confiança:</strong> 0.75</li>\n  <li><strong>Score de Inovação:</strong> 0.68</li>\n  <li><strong>Score de Sustentabilidade:</strong> 0.82</li>\n</ul>\n\n<h2>Insights Estratégicos</h2>\n\n<h3>Oportunidades de Melhoria</h3>\n\n<ul>\n  <li>Ampliar comunicação sobre estratégia de longo prazo</li>\n  <li>Melhorar engajamento em redes sociais</li>\n</ul>\n\n<h3>Alertas de Risco</h3>\n\n<ul>\n  <li>Volatilidade do preço do petróleo</li>\n  <li>Mudanças regulatórias no setor</li>\n</ul>\n\n<h3>Análise Estratégica</h3>\n\n<p>A Petrobras mantém uma <strong>posição competitiva forte</strong> no mercado, com tendência de <strong>crescimento</strong> na participação de mercado. As principais iniciativas estratégicas incluem:</p>\n\n<ul>\n  <li>Expansão em energia renovável</li>\n  <li>Modernização de infraestrutura</li>\n</ul>\n\n<h2>Recomendação</h2>\n\n<p>Com base na análise consolidada dos dados financeiros e de sentimento de mercado, <strong>recomenda-se manter</strong> a posição atual, monitorando de perto os desenvolvimentos estratégicos da empresa e as condições macroeconômicas do setor.</p>\n\n<p><em>Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial e requer revisão humana antes da publicação. As informações apresentadas não constituem recomendação de investimento. Consulte sempre um analista financeiro certificado antes de tomar decisões de investimento.</em></p>",
      "status": "pendente_revisao",
      "recomendacao": "Recomenda-se manter a posição atual, monitorando de perto os desenvolvimentos estratégicos da empresa e as condições macroeconômicas do setor.",
      "metadata": {
        "generated_at": "2025-12-03T10:35:00.000000Z",
        "agent": "key",
        "agent_version": "1.0",
        "flow": "julia->pedro->key",
        "format": "html",
        "financial_data_collected_at": "2025-12-03T10:00:00.000000Z",
        "sentiment_analyzed_at": "2025-12-03T10:30:00.000000Z"
      },
      "financial_data_id": 89,
      "sentiment_analysis_id": 42,
      "stock_symbol_id": 5,
      "created_at": "2025-12-03T10:35:00.000000Z"
    },
    "records_created": 1,
    "symbols_processed": ["PETR4"]
  }
}
```

**Nota**: O Agente Key coleta e consolida os dados dos Agentes Júlia (dados financeiros) e Pedro (análise de sentimento) para gerar uma matéria jornalística profissional em formato HTML, pronta para revisão humana.

#### POST `/api/agents/publish-notify`
Executa Agente PublishNotify

#### POST `/api/agents/cleanup`
Executa Agente Cleanup

---

### Orquestração

#### POST `/api/orchestrate`
Executa fluxo completo: Júlia → Pedro → Key → Revisão

**Request**:
```json
{
  "company_name": "Petrobras"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Orquestração concluída com sucesso",
  "logs": [
    {"agent": "Julia", "message": "Dados coletados para Petrobras"},
    {"agent": "Pedro", "message": "Análise de sentimento concluída"},
    {"agent": "Key", "message": "Artigo gerado com sucesso"}
  ],
  "financial_data": { ... },
  "sentiment_data": { ... },
  "article": {
    "id": 123,
    "title": "Análise: Petrobras...",
    "html_content": "<h1>...</h1>",
    "status": "pendente_revisao"
  },
  "article_id": 123
}
```

#### POST `/api/orchestrate/{articleId}/review`
Processa decisão de aprovação/rejeição

**Request**:
```json
{
  "decision": "approve"  // ou "reject"
}
```

**Se reject**:
```json
{
  "decision": "reject",
  "motivo_reprovacao": "Conteúdo não adequado"
}
```

**Response** (200):
```json
{
  "success": true,
  "status": "published",  // ou "rejected"
  "message": "Artigo publicado com sucesso",
  "article": { ... }
}
```

---

### Ações Monitoradas (Stock Symbols)

#### GET `/api/stock-symbols`
Lista todas as ações monitoradas

**Response** (200):
```json
{
  "data": [
    {
      "id": 1,
      "symbol": "PETR4",
      "company_name": "Petrobras",
      "is_active": true,
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ]
}
```

#### POST `/api/stock-symbols` (Requer autenticação)
Cria nova ação monitorada

**Request**:
```json
{
  "symbol": "VALE3",
  "company_name": "Vale S.A.",
  "is_active": true
}
```

#### GET `/api/stock-symbols/{id}`
Visualiza ação específica

#### PUT `/api/stock-symbols/{id}` (Requer autenticação)
Atualiza ação

#### DELETE `/api/stock-symbols/{id}` (Requer autenticação)
Deleta ação

---

### Dados Financeiros

#### GET `/api/financial-data`
Lista todos os dados financeiros

**Query Parameters**:
- `symbol`: Filtrar por símbolo
- `limit`: Limite de resultados
- `offset`: Offset para paginação

#### GET `/api/financial-data/{id}`
Visualiza dado financeiro específico

#### GET `/api/financial-data/symbol/{symbol}/latest`
Último dado financeiro de uma ação

---

### Análise de Sentimento

#### GET `/api/sentiment-analysis`
Lista todas as análises de sentimento

**Query Parameters**:
- `symbol`: Filtrar por símbolo
- `sentiment`: Filtrar por sentimento (positive/negative/neutral)
- `limit`: Limite de resultados

#### GET `/api/sentiment-analysis/{id}`
Visualiza análise específica

#### GET `/api/sentiment-analysis/symbol/{symbol}/latest`
Última análise de uma ação

---

### Artigos

#### GET `/api/articles`
Lista todos os artigos

**Query Parameters**:
- `status`: Filtrar por status (pendente_revisao/aprovado/reprovado/publicado)
- `symbol`: Filtrar por símbolo
- `limit`: Limite de resultados

#### GET `/api/articles/{id}`
Visualiza artigo específico

#### POST `/api/articles/{id}/approve` (Requer autenticação)
Aprova artigo

**Response** (200):
```json
{
  "success": true,
  "message": "Artigo aprovado com sucesso",
  "article": {
    "id": 123,
    "status": "aprovado",
    "reviewed_at": "2025-12-03 10:00:00"
  }
}
```

#### POST `/api/articles/{id}/reject` (Requer autenticação)
Reprova artigo

**Request**:
```json
{
  "motivo_reprovacao": "Conteúdo não adequado"
}
```

#### POST `/api/articles/{id}/publish` (Requer autenticação)
Publica artigo aprovado

#### DELETE `/api/articles/{id}` (Requer autenticação)
Deleta artigo

---

### Análises (Jobs)

#### POST `/api/analyses` (Requer autenticação)
Solicita nova análise completa (via Jobs)

**Request**:
```json
{
  "symbol": "PETR4",
  "company_name": "Petrobras"
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Análise solicitada com sucesso",
  "analysis": {
    "id": 1,
    "status": "fetching_financial_data",
    "symbol": "PETR4"
  }
}
```

#### GET `/api/analyses`
Lista todas as análises

#### GET `/api/analyses/{id}`
Visualiza análise específica

---

## 📁 Estrutura do Projeto

```
residencia_2025_02_API_COM_LARAVEL/
│
├── src/                          # Aplicação Laravel
│   ├── app/
│   │   ├── Console/
│   │   │   └── Commands/         # Comandos Artisan dos Agentes
│   │   │       ├── AgentJuliaFetch.php
│   │   │       ├── AgentPedroAnalyze.php
│   │   │       ├── AgentKeyCompose.php
│   │   │       ├── AgentPublishNotify.php
│   │   │       └── AgentCleanup.php
│   │   ├── Http/
│   │   │   ├── Controllers/      # Controllers da API
│   │   │   │   ├── OrchestrationController.php
│   │   │   │   ├── AgentController.php
│   │   │   │   ├── ArticleController.php
│   │   │   │   ├── FinancialDataController.php
│   │   │   │   ├── SentimentAnalysisController.php
│   │   │   │   └── StockSymbolController.php
│   │   │   ├── Middleware/       # Middlewares
│   │   │   └── Requests/         # Form Requests (Validação)
│   │   ├── Jobs/                 # Jobs Assíncronos
│   │   │   ├── FetchFinancialDataJob.php
│   │   │   ├── AnalyzeMarketSentimentJob.php
│   │   │   ├── DraftArticleJob.php
│   │   │   └── NotifyReviewerJob.php
│   │   ├── Models/               # Models Eloquent
│   │   │   ├── StockSymbol.php
│   │   │   ├── FinancialData.php
│   │   │   ├── SentimentAnalysis.php
│   │   │   ├── Article.php
│   │   │   ├── Analysis.php
│   │   │   └── User.php
│   │   └── Services/             # Serviços de Integração
│   │       ├── YahooFinanceService.php
│   │       ├── NewsAnalysisService.php
│   │       ├── LLMService.php
│   │       ├── GeminiResponseService.php
│   │       └── AgentJuliaJsonManager.php
│   ├── database/
│   │   ├── migrations/           # Migrations do Banco
│   │   ├── seeders/              # Seeders
│   │   └── factories/            # Factories para Testes
│   ├── llm/                      # Serviço Python LLM
│   │   ├── main.py
│   │   ├── models/
│   │   ├── scripts/
│   │   └── utils/
│   ├── resources/
│   │   ├── views/                # Views Blade
│   │   │   ├── dashboard.blade.php
│   │   │   ├── orchestrate.blade.php
│   │   │   ├── articles/
│   │   │   └── sentiment/
│   │   ├── css/
│   │   └── js/
│   ├── routes/
│   │   ├── api.php               # Rotas da API
│   │   └── web.php               # Rotas Web
│   ├── storage/                  # Arquivos de armazenamento
│   ├── tests/                    # Testes
│   └── .env                      # Variáveis de ambiente
│
├── DockerConfig/                 # Configurações Docker
│   ├── entrypoint.sh
│   └── nginx/
│       └── default.conf
│
├── docker-compose.yaml           # Configuração Docker Compose
├── Dockerfile                    # Dockerfile da aplicação
├── README.MD                     # Este arquivo

```

---

## 🔄 Fluxo Completo do Sistema

### Fluxo Principal: Orquestração Completa

```
1. USUÁRIO INICIA ORQUESTRAÇÃO
   │
   ├─► POST /api/orchestrate { "company_name": "Petrobras" }
   │
   ▼
2. AGENTE JÚLIA (Coleta de Dados)
   │
   ├─► Busca ou cria StockSymbol
   ├─► Consulta Google Gemini API
   ├─► Processa dados financeiros
   ├─► Salva em FinancialData
   └─► Retorna dados completos
   │
   ▼
3. AGENTE PEDRO (Análise de Sentimento)
   │
   ├─► Recebe dados de Júlia
   ├─► Consulta News API
   ├─► Analisa sentimento das notícias
   ├─► Gera análise enriquecida
   ├─► Salva em SentimentAnalysis
   └─► Retorna dados completos
   │
   ▼
4. AGENTE KEY (Geração de Conteúdo)
   │
   ├─► Recebe dados de Júlia e Pedro
   ├─► Consolida todos os dados
   ├─► Consulta Google Gemini API
   ├─► Gera artigo jornalístico
   ├─► Extrai recomendação
   ├─► Salva em Article (status: pendente_revisao)
   └─► Retorna artigo para revisão
   │
   ▼
5. REVISÃO HUMANA
   │
   ├─► Revisor visualiza artigo
   ├─► Aprova ou Reprova
   │
   ├─► Se APROVADO:
   │   └─► Status → aprovado
   │       └─► Pode ser publicado
   │
   └─► Se REPROVADO:
       └─► Status → reprovado
           └─► Salvo para re-análise
   │
   ▼
6. PUBLICAÇÃO (Opcional)
   │
   ├─► Revisor publica artigo aprovado
   └─► Status → publicado
       └─► published_at = now()
```

### Fluxo Alternativo: Processamento Assíncrono (Jobs)

```
1. USUÁRIO SOLICITA ANÁLISE
   │
   ├─► POST /api/analyses { "symbol": "PETR4" }
   │
   ▼
2. CRIA ANALYSIS RECORD
   │
   ├─► Status: fetching_financial_data
   │
   ▼
3. DISPARA JOBS EM CADEIA
   │
   ├─► FetchFinancialDataJob (Júlia)
   │   └─► Ao concluir, dispara próximo job
   │
   ├─► AnalyzeMarketSentimentJob (Pedro)
   │   └─► Ao concluir, dispara próximo job
   │
   ├─► DraftArticleJob (Key)
   │   └─► Ao concluir, dispara próximo job
   │
   └─► NotifyReviewerJob (PublishNotify)
       └─► Envia email para revisores
   │
   ▼
4. ANALYSIS COMPLETA
   │
   └─► Status: pending_review
```

---

## 📚 Documentação Adicional

### Documentos Disponíveis

- **[ARQUITETURA.md](ARQUITETURA.md)**: Arquitetura completa do sistema
- **[DIAGRAMAS.md](DIAGRAMAS.md)**: Diagramas visuais do sistema
- **[INICIALIZACAO_COMPLETA.md](INICIALIZACAO_COMPLETA.md)**: Guia completo de inicialização
- **[GUIA_COMPLETO_SISTEMA.md](GUIA_COMPLETO_SISTEMA.md)**: Guia completo do sistema
- **[FLUXO_COMPLETO_SISTEMA.md](FLUXO_COMPLETO_SISTEMA.md)**: Fluxo completo detalhado
- **[RELATORIO_VERIFICACAO_ERROS.md](RELATORIO_VERIFICACAO_ERROS.md)**: Relatório de verificação de erros

### Documentação de Código

- **Controllers**: Documentação inline nos arquivos PHP
- **Services**: Documentação inline nos arquivos PHP
- **Models**: Documentação inline nos arquivos PHP
- **Jobs**: Documentação inline nos arquivos PHP

---

## 🔧 Troubleshooting

### Problemas Comuns

#### 1. Erro ao iniciar containers
```bash
# Verificar se Docker está rodando
docker ps

# Verificar logs
docker-compose logs

# Reconstruir containers
docker-compose down
docker-compose up --build -d
```

#### 2. Erro de conexão com banco de dados
```bash
# Verificar se banco está rodando
docker-compose ps db

# Verificar logs do banco
docker-compose logs db

# Aguardar inicialização (30 segundos)
sleep 30
```

#### 3. Erro de permissões
```bash
# Dentro do container
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 4. Erro de API Keys
- Verificar se `GEMINI_API_KEY` está configurada no `.env`
- Verificar se `NEWS_API_KEY` está configurada no `.env`
- Verificar se as chaves são válidas

#### 5. Erro ao executar agentes
```bash
# Verificar logs
docker-compose logs app

# Verificar logs específicos do agente
tail -f src/storage/logs/laravel.log
```

#### 6. Erro de Python (LLM Service)
```bash
# Verificar se serviço LLM está rodando
docker-compose ps llm

# Verificar logs
docker-compose logs llm
```

### Comandos Úteis

```bash
# Limpar cache
docker exec -it laravel_app php artisan cache:clear
docker exec -it laravel_app php artisan config:clear

# Recriar banco de dados
docker exec -it laravel_app php artisan migrate:fresh --seed

# Ver rotas disponíveis
docker exec -it laravel_app php artisan route:list

# Ver logs em tempo real
docker-compose logs -f app
```

---

## 👥 Desenvolvimento e Contribuição

### Estrutura de Desenvolvimento

1. **Branch Principal**: `main` ou `master`
2. **Branches de Feature**: `feature/nome-da-feature`
3. **Branches de Bugfix**: `bugfix/nome-do-bug`

### Padrões de Código

- **PSR-12**: Padrão de codificação PHP
- **Laravel Conventions**: Seguir convenções do Laravel
- **Documentação**: Documentar métodos públicos
- **Testes**: Escrever testes para novas funcionalidades

### Executar Testes

```bash
# Dentro do container
docker exec -it laravel_app php artisan test

# Testes específicos
docker exec -it laravel_app php artisan test --filter AgentJuliaTest
```

### Adicionar Novo Agente

1. Criar Command: `php artisan make:command AgentNovoAgente`
2. Criar Service (se necessário): `app/Services/NovoAgenteService.php`
3. Adicionar rota em `routes/api.php`
4. Adicionar método em `AgentController.php`
5. Documentar no README

### Melhorias Futuras

- [ ] Dashboard administrativo completo
- [ ] Notificações em tempo real (WebSockets)
- [ ] Análise de tendências de mercado
- [ ] Recomendações personalizadas
- [ ] Integração com mais fontes de dados
- [ ] Cache de dados frequentes
- [ ] Processamento assíncrono completo
- [ ] Métricas e monitoramento avançado

---

## 📝 Licença

Este projeto é parte de um projeto acadêmico de mentoria.

---
