# 🏗️ Arquitetura Completa do Projeto

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura de Componentes](#arquitetura-de-componentes)
3. [Stack Tecnológico](#stack-tecnológico)
4. [Fluxo de Dados Completo](#fluxo-de-dados-completo)
5. [Agentes de IA](#agentes-de-ia)
6. [Estrutura de Dados](#estrutura-de-dados)
7. [APIs e Endpoints](#apis-e-endpoints)
8. [Fluxo de Processamento](#fluxo-de-processamento)
9. [Segurança e Compliance](#segurança-e-compliance)
10. [Deploy e Infraestrutura](#deploy-e-infraestrutura)

---

## 🎯 Visão Geral

### Proposta

Sistema de agentes de inteligência artificial para analisar ações e gerar conteúdos recomendando (ou não) a compra, com base em dados reais e percepção de mercado.

### Objetivo

Produzir matérias financeiras claras, confiáveis e baseadas em dados, com apoio de IA e curadoria humana.

### Principais Funcionalidades

- **Coleta de Dados Financeiros**: Coleta automática de dados de mercado
- **Análise de Sentimento**: Análise de notícias e mídia sobre empresas
- **Geração de Conteúdo**: Criação automática de matérias financeiras usando LLM
- **Revisão Humana**: Sistema de aprovação/reprovação por revisores
- **Publicação**: Publicação de matérias aprovadas
- **Manutenção**: Limpeza e otimização automática do sistema

---

## 🏛️ Arquitetura de Componentes

### Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENTE/API                             │
│                    (Frontend/Mobile/API)                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ HTTP/REST
                             │
┌────────────────────────────▼─────────────────────────────────────┐
│                    LARAVEL APPLICATION                           │
│  ┌───────────────────────────────────────────────────────────┐   │
│  │                  API Layer (REST)                         │   │
│  │  - AgentController                                        │   │
│  │  - StockSymbolController                                  │   │
│  │  - FinancialDataController                                │   │
│  │  - SentimentAnalysisController                            │   │
│  │  - ArticleController                                      │   │
│  └───────────────────────────────────────────────────────────┘   │
│                             │                                    │
│  ┌──────────────────────────▼────────────────────────────────┐   │
│  │              Business Logic Layer                         │   │
│  │  - Commands (Agentes)                                     │   │
│  │    • AgentJuliaFetch                                      │   │
│  │    • AgentPedroAnalyze                                    │   │
│  │    • AgentKeyCompose                                      │   │
│  │    • AgentPublishNotify                                   │   │
│  │    • AgentCleanup                                         │   │
│  │  - Services                                               │   │
│  │    • YahooFinanceService (OpenAI)                         │   │
│  │    • NewsAnalysisService                                  │   │
│  │    • LLMService                                           │   │
│  └──────────────────────────┬────────────────────────────────┘   │
│                             │                                    │
│  ┌──────────────────────────▼────────────────────────────────┐   │
│  │              Data Access Layer                            │   │
│  │  - Models                                                 │   │
│  │    • StockSymbol                                          │   │
│  │    • FinancialData                                        │   │
│  │    • SentimentAnalysis                                    │   │
│  │    • Article                                              │   │
│  │    • User                                                 │   │
│  └──────────────────────────┬────────────────────────────────┘   │
└─────────────────────────────┼────────────────────────────────────┘
                              │
                              │ Eloquent ORM
                              │
┌─────────────────────────────▼────────────────────────────────────┐
│                      MYSQL DATABASE                              │
│  - stock_symbols                                                 │
│  - financial_data                                                │
│  - sentiment_analysis                                            │
│  - articles                                                      │
│  - users                                                         │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                            │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  OpenAI API (Agente Júlia)                               │   │
│  │  - Dados financeiros                                     │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  News API (Agente Pedro)                                 │   │
│  │  - Notícias e análise de sentimento                      │   │
│  └──────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  LLM Service (Python) (Agente Key)                       │   │
│  │  - Geração de conteúdo                                   │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes Principais

#### 1. **API Layer (Camada de API)**
- **Controllers**: Gerenciam requisições HTTP
- **Middleware**: Autenticação, rate limiting, CORS
- **Requests**: Validação de dados de entrada
- **Policies**: Autorização e controle de acesso

#### 2. **Business Logic Layer (Camada de Lógica de Negócio)**
- **Commands**: Agentes de IA (Artisan Commands)
- **Services**: Serviços de integração com APIs externas
- **Jobs**: Processamento assíncrono (futuro)
- **Events**: Eventos do sistema (futuro)

#### 3. **Data Access Layer (Camada de Acesso a Dados)**
- **Models**: Modelos Eloquent ORM
- **Migrations**: Estrutura do banco de dados
- **Seeders**: Dados iniciais
- **Factories**: Dados de teste

#### 4. **External Services (Serviços Externos)**
- **OpenAI API**: Dados financeiros
- **News API**: Notícias e análise de sentimento
- **LLM Service (Python)**: Geração de conteúdo

---

## 🛠️ Stack Tecnológico

### Backend

- **Laravel 8**: Framework PHP
- **PHP 8.0**: Linguagem de programação
- **MySQL 5.7**: Banco de dados
- **Composer**: Gerenciador de dependências PHP

### Frontend (Futuro)

- **HTML, CSS, JavaScript**: Interface web
- **Vue.js/React**: Framework frontend (opcional)

### Serviços Externos

- **OpenAI API**: Coleta de dados financeiros
- **News API**: Análise de notícias e sentimento
- **Python 3.9**: Serviço LLM para geração de conteúdo

### Infraestrutura

- **Docker**: Containerização
- **Docker Compose**: Orquestração de containers
- **Nginx**: Servidor web
- **PHP-FPM**: Processador PHP

### Ferramentas de Desenvolvimento

- **Git**: Controle de versão
- **PHPUnit**: Testes unitários
- **Artisan**: CLI do Laravel

---

## 🔄 Fluxo de Dados Completo

### Fluxo Principal: Coleta → Análise → Geração → Revisão → Publicação

```
1. COLETA DE DADOS (Agente Júlia)
   │
   ├─► Busca ações monitoradas (StockSymbol)
   ├─► Chama OpenAI API para cada ação
   ├─► Processa e normaliza dados financeiros
   └─► Salva em FinancialData
       │
       └─► Dados: price, volume, market_cap, pe_ratio, etc.

2. ANÁLISE DE SENTIMENTO (Agente Pedro)
   │
   ├─► Busca ações monitoradas (StockSymbol)
   ├─► Chama News API para cada ação
   ├─► Analisa sentimento das notícias
   └─► Salva em SentimentAnalysis
       │
       └─► Dados: sentiment, sentiment_score, news_count, etc.

3. GERAÇÃO DE CONTEÚDO (Agente Key)
   │
   ├─► Busca ações com dados financeiros e análise recentes
   ├─► Consolida dados de FinancialData + SentimentAnalysis
   ├─► Chama LLM Service (Python) para gerar artigo
   └─► Salva em Article (status: pendente_revisao)
       │
       └─► Dados: title, content, recomendacao, metadata

4. NOTIFICAÇÃO (Agente PublishNotify)
   │
   ├─► Busca artigos pendentes de revisão
   ├─► Envia email para revisores
   └─► Marca artigos como notificados

5. REVISÃO HUMANA (Manual via API)
   │
   ├─► Revisor acessa API
   ├─► Visualiza artigo pendente
   ├─► Aprova ou Reprova
   └─► Se aprovado: status → aprovado
       Se reprovado: status → reprovado

6. PUBLICAÇÃO (Manual via API)
   │
   ├─► Revisor publica artigo aprovado
   └─► Status → publicado
       │
       └─► published_at = now()
```

### Fluxo de Dados Detalhado

#### 1. Agente Júlia (Coleta de Dados)

```
StockSymbol (ativo)
    │
    ├─► YahooFinanceService.getQuote(symbol)
    │       │
    │       ├─► OpenAI API Request
    │       │       │
    │       │       └─► Prompt estruturado
    │       │
    │       └─► Resposta JSON
    │               │
    │               └─► Normalização de dados
    │
    └─► FinancialData.create()
            │
            └─► Campos: price, volume, market_cap, etc.
```

#### 2. Agente Pedro (Análise de Sentimento)

```
StockSymbol (ativo)
    │
    ├─► NewsAnalysisService.searchNews(symbol)
    │       │
    │       ├─► News API Request
    │       │       │
    │       │       └─► Busca notícias sobre a empresa
    │       │
    │       └─► Array de notícias
    │
    ├─► NewsAnalysisService.analyzeSentiment(articles)
    │       │
    │       └─► Análise de sentimento
    │               │
    │               └─► positive_count, negative_count, neutral_count
    │
    └─► SentimentAnalysis.create()
            │
            └─► Campos: sentiment, sentiment_score, news_count, etc.
```

#### 3. Agente Key (Geração de Conteúdo)

```
StockSymbol (ativo)
    │
    ├─► FinancialData (último)
    │       │
    │       └─► Dados financeiros recentes
    │
    ├─► SentimentAnalysis (última)
    │       │
    │       └─► Análise de sentimento recente
    │
    ├─► LLMService.generateArticle()
    │       │
    │       ├─► Prepara dados de entrada
    │       │       │
    │       │       └─► JSON com financial + sentiment
    │       │
    │       ├─► Chama Python Script (run_llm.py)
    │       │       │
    │       │       ├─► Formata dados (llm_utils.py)
    │       │       │
    │       │       └─► Gera artigo (generate_article_content)
    │       │
    │       └─► Retorna título e conteúdo
    │
    └─► Article.create()
            │
            └─► Campos: title, content, status, recomendacao
```

#### 4. Agente PublishNotify (Notificação)

```
Article (pendente_revisao, não notificado)
    │
    ├─► Busca artigos pendentes
    │
    ├─► Prepara email
    │       │
    │       └─► Lista de artigos pendentes
    │
    ├─► Envia email para revisores
    │
    └─► Marca artigos como notificados
            │
            └─► notified_at = now()
```

#### 5. Revisão Humana (Manual)

```
Article (pendente_revisao)
    │
    ├─► GET /api/articles/{id}
    │       │
    │       └─► Visualiza artigo
    │
    ├─► POST /api/articles/{id}/approve
    │       │
    │       └─► Status → aprovado
    │               │
    │               └─► reviewed_at = now()
    │
    └─► POST /api/articles/{id}/reject
            │
            └─► Status → reprovado
                    │
                    └─► motivo_reprovacao
```

#### 6. Publicação (Manual)

```
Article (aprovado)
    │
    ├─► POST /api/articles/{id}/publish
    │       │
    │       └─► Status → publicado
    │               │
    │               └─► published_at = now()
```

---

## 🤖 Agentes de IA

### 1. Agente Júlia (Coleta de Dados Financeiros)

**Responsabilidade**: Coletar dados financeiros atualizados via OpenAI API

**Frequência**: A cada 10 minutos

**Comando**: `agent:julia:fetch`

**Entrada**:
- Símbolos de ações ativas (StockSymbol)

**Processamento**:
1. Busca ações monitoradas
2. Para cada ação, chama OpenAI API
3. Processa e normaliza dados
4. Salva em FinancialData

**Saída**:
- Dados financeiros (FinancialData)
- Logs de execução

**Serviço**: YahooFinanceService (OpenAI)

**Log**: `storage/logs/agent_julia.log`

### 2. Agente Pedro (Análise de Sentimento)

**Responsabilidade**: Analisar o que o mercado e a mídia estão dizendo sobre a empresa

**Frequência**: A cada hora

**Comando**: `agent:pedro:analyze`

**Entrada**:
- Símbolos de ações ativas (StockSymbol)

**Processamento**:
1. Busca ações monitoradas
2. Para cada ação, busca notícias (News API)
3. Analisa sentimento das notícias
4. Salva em SentimentAnalysis

**Saída**:
- Análise de sentimento (SentimentAnalysis)
- Logs de execução

**Serviço**: NewsAnalysisService

**Log**: `storage/logs/agent_pedro.log`

### 3. Agente Key (Geração de Conteúdo)

**Responsabilidade**: Gerar rascunho de matéria financeira baseado nos dados dos outros agentes

**Frequência**: A cada 30 minutos

**Comando**: `agent:key:compose`

**Entrada**:
- Dados financeiros (FinancialData)
- Análise de sentimento (SentimentAnalysis)

**Processamento**:
1. Busca ações com dados recentes (últimas 24h)
2. Consolida dados financeiros + análise de sentimento
3. Chama LLM Service (Python) para gerar artigo
4. Extrai recomendação do conteúdo
5. Salva em Article (status: pendente_revisao)

**Saída**:
- Artigo gerado (Article)
- Logs de execução

**Serviço**: LLMService (Python)

**Log**: `storage/logs/agent_key.log`

### 4. Agente PublishNotify (Notificação)

**Responsabilidade**: Verificar matérias pendentes e notificar revisores humanos

**Frequência**: A cada 15 minutos

**Comando**: `agent:publish:notify`

**Entrada**:
- Artigos pendentes de revisão (Article)

**Processamento**:
1. Busca artigos pendentes não notificados
2. Prepara email com lista de artigos
3. Envia email para revisores
4. Marca artigos como notificados

**Saída**:
- Emails enviados
- Artigos marcados como notificados
- Logs de execução

**Serviço**: Mail (Laravel)

**Log**: `storage/logs/agent_notify.log`

### 5. Agente Cleanup (Limpeza)

**Responsabilidade**: Limpar arquivos temporários, caches antigos e manter o sistema organizado

**Frequência**: Diário às 03:00

**Comando**: `agent:cleanup`

**Entrada**:
- Logs antigos
- Arquivos temporários
- Caches antigos
- Dados financeiros antigos (opcional)
- Análises de sentimento antigas (opcional)

**Processamento**:
1. Limpa logs antigos (>30 dias)
2. Limpa arquivos temporários (>30 dias)
3. Limpa caches antigos
4. Limpa dados financeiros antigos (>90 dias)
5. Limpa análises de sentimento antigas (>90 dias)

**Saída**:
- Arquivos removidos
- Logs de execução

**Serviço**: Storage, Cache (Laravel)

**Log**: `storage/logs/agent_cleanup.log`

---

## 💾 Estrutura de Dados

### Modelo de Dados

```
StockSymbol
    │
    ├─► id
    ├─► symbol (PETR4)
    ├─► company_name
    ├─► is_active
    ├─► is_default
    │
    ├─► financialData (HasMany)
    ├─► sentimentAnalyses (HasMany)
    └─► articles (HasMany)

FinancialData
    │
    ├─► id
    ├─► stock_symbol_id (FK)
    ├─► symbol
    ├─► price
    ├─► previous_close
    ├─► change
    ├─► change_percent
    ├─► volume
    ├─► market_cap
    ├─► pe_ratio
    ├─► dividend_yield
    ├─► high_52w
    ├─► low_52w
    ├─► raw_data (JSON)
    ├─► source
    ├─► collected_at
    │
    └─► stockSymbol (BelongsTo)

SentimentAnalysis
    │
    ├─► id
    ├─► stock_symbol_id (FK)
    ├─► symbol
    ├─► sentiment (positive/negative/neutral)
    ├─► sentiment_score
    ├─► news_count
    ├─► positive_count
    ├─► negative_count
    ├─► neutral_count
    ├─► trending_topics
    ├─► news_sources (JSON)
    ├─► raw_data (JSON)
    ├─► source
    ├─► analyzed_at
    │
    └─► stockSymbol (BelongsTo)

Article
    │
    ├─► id
    ├─► stock_symbol_id (FK)
    ├─► symbol
    ├─► financial_data_id (FK)
    ├─► sentiment_analysis_id (FK)
    ├─► title
    ├─► content
    ├─► status (pendente_revisao/aprovado/reprovado/publicado)
    ├─► motivo_reprovacao
    ├─► recomendacao
    ├─► metadata (JSON)
    ├─► notified_at
    ├─► reviewed_at
    ├─► reviewed_by (FK)
    ├─► published_at
    │
    ├─► stockSymbol (BelongsTo)
    ├─► financialData (BelongsTo)
    ├─► sentimentAnalysis (BelongsTo)
    └─► reviewer (BelongsTo)

User
    │
    ├─► id
    ├─► name
    ├─► email
    ├─► password
    │
    └─► reviewedArticles (HasMany)
```

### Relacionamentos

- **StockSymbol** → **FinancialData** (1:N)
- **StockSymbol** → **SentimentAnalysis** (1:N)
- **StockSymbol** → **Article** (1:N)
- **FinancialData** → **StockSymbol** (N:1)
- **SentimentAnalysis** → **StockSymbol** (N:1)
- **Article** → **StockSymbol** (N:1)
- **Article** → **FinancialData** (N:1)
- **Article** → **SentimentAnalysis** (N:1)
- **Article** → **User** (N:1) [reviewer]
- **User** → **Article** (1:N) [reviewedArticles]

---

## 🔌 APIs e Endpoints

### Autenticação

- **POST** `/api/user` - Autenticação JWT

### Agentes

- **GET** `/api/agents/status` - Status dos agentes
- **POST** `/api/agents/julia` - Executa Agente Júlia
- **POST** `/api/agents/pedro` - Executa Agente Pedro
- **POST** `/api/agents/key` - Executa Agente Key
- **POST** `/api/agents/publish-notify` - Executa Agente PublishNotify
- **POST** `/api/agents/cleanup` - Executa Agente Cleanup

### Ações Monitoradas (Stock Symbols)

- **GET** `/api/stock-symbols` - Lista todas as ações
- **POST** `/api/stock-symbols` - Cria nova ação (requer autenticação)
- **GET** `/api/stock-symbols/{id}` - Visualiza ação específica
- **PUT** `/api/stock-symbols/{id}` - Atualiza ação (requer autenticação)
- **DELETE** `/api/stock-symbols/{id}` - Deleta ação (requer autenticação)

### Dados Financeiros

- **GET** `/api/financial-data` - Lista todos os dados financeiros
- **GET** `/api/financial-data/{id}` - Visualiza dado financeiro específico
- **GET** `/api/financial-data/symbol/{symbol}/latest` - Último dado financeiro de uma ação

### Análise de Sentimento

- **GET** `/api/sentiment-analysis` - Lista todas as análises
- **GET** `/api/sentiment-analysis/{id}` - Visualiza análise específica
- **GET** `/api/sentiment-analysis/symbol/{symbol}/latest` - Última análise de uma ação

### Artigos

- **GET** `/api/articles` - Lista todos os artigos
- **GET** `/api/articles/{id}` - Visualiza artigo específico
- **POST** `/api/articles/{id}/approve` - Aprova artigo (requer autenticação)
- **POST** `/api/articles/{id}/reject` - Reprova artigo (requer autenticação)
- **POST** `/api/articles/{id}/publish` - Publica artigo (requer autenticação)
- **DELETE** `/api/articles/{id}` - Deleta artigo (requer autenticação)

### Agenda (Legacy)

- **GET** `/api/agenda` - Lista todas as agendas
- **POST** `/api/agenda` - Cria nova agenda
- **GET** `/api/agenda/{id}` - Visualiza agenda específica
- **PUT** `/api/agenda/{id}` - Atualiza agenda
- **DELETE** `/api/agenda/{id}` - Deleta agenda

---

## 🔄 Fluxo de Processamento

### Fluxo Completo: Do Início ao Fim

```
┌─────────────────────────────────────────────────────────────────┐
│                    INICIALIZAÇÃO DO SISTEMA                     │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│              1. COLETA DE DADOS (Agente Júlia)                   │
│  Schedule: A cada 10 minutos                                     │
│  ─────────────────────────────────────────────────────────────   │
│  1.1. Busca ações ativas (StockSymbol)                           │
│  1.2. Para cada ação:                                            │
│       - Chama OpenAI API                                         │
│       - Processa dados financeiros                               │
│       - Salva em FinancialData                                   │
│  1.3. Log de execução                                            │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│           2. ANÁLISE DE SENTIMENTO (Agente Pedro)                │
│  Schedule: A cada hora                                           │
│  ──────────────────────────────────────────────────────────────  │
│  2.1. Busca ações ativas (StockSymbol)                           │
│  2.2. Para cada ação:                                            │
│       - Busca notícias (News API)                                │
│       - Analisa sentimento                                       │
│       - Salva em SentimentAnalysis                               │
│  2.3. Log de execução                                            │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│           3. GERAÇÃO DE CONTEÚDO (Agente Key)                    │
│  Schedule: A cada 30 minutos                                     │
│  ─────────────────────────────────────────────────────────────   │
│  3.1. Busca ações com dados recentes (últimas 24h)               │
│  3.2. Para cada ação:                                            │
│       - Busca FinancialData (último)                             │
│       - Busca SentimentAnalysis (última)                         │
│       - Consolida dados                                          │
│       - Chama LLM Service (Python)                               │
│       - Gera artigo (título + conteúdo)                          │
│       - Extrai recomendação                                      │
│       - Salva em Article (status: pendente_revisao)              │
│  3.3. Log de execução                                            │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│             4. NOTIFICAÇÃO (Agente PublishNotify)                │
│  Schedule: A cada 15 minutos                                     │
│  ─────────────────────────────────────────────────────────────   │
│  4.1. Busca artigos pendentes não notificados                    │
│  4.2. Prepara email com lista de artigos                         │
│  4.3. Envia email para revisores                                 │
│  4.4. Marca artigos como notificados                             │
│  4.5. Log de execução                                            │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│                   5. REVISÃO HUMANA (Manual)                     │
│  ─────────────────────────────────────────────────────────────   │
│  5.1. Revisor acessa API                                         │
│  5.2. Visualiza artigos pendentes                                │
│  5.3. Aprova ou Reprova                                          │
│       - Se aprovado: status → aprovado                           │
│       - Se reprovado: status → reprovado + motivo                │
│  5.4. Log de ação                                                │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│                   6. PUBLICAÇÃO (Manual)                         │
│  ─────────────────────────────────────────────────────────────   │
│  6.1. Revisor publica artigo aprovado                            │
│  6.2. Status → publicado                                         │
│  6.3. published_at = now()                                       │
│  6.4. Log de publicação                                          │
└──────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│                 7. LIMPEZA (Agente Cleanup)                      │
│  Schedule: Diário às 03:00                                       │
│  ─────────────────────────────────────────────────────────────   │
│  7.1. Limpa logs antigos (>30 dias)                              │
│  7.2. Limpa arquivos temporários (>30 dias)                      │
│  7.3. Limpa caches antigos                                       │
│  7.4. Limpa dados financeiros antigos (>90 dias)                 │
│  7.5. Limpa análises de sentimento antigas (>90 dias)            │
│  7.6. Log de execução                                            │
└──────────────────────────────────────────────────────────────────┘
```

### Fluxo de Execução por Agente

#### Agente Júlia (Coleta de Dados)

```
Início (Schedule: A cada 10 minutos)
    │
    ├─► Busca ações ativas (StockSymbol::active()->get())
    │
    ├─► Para cada ação:
    │       │
    │       ├─► YahooFinanceService.getQuote(symbol)
    │       │       │
    │       │       ├─► Constrói prompt para OpenAI
    │       │       │
    │       │       ├─► Chama OpenAI API
    │       │       │       │
    │       │       │       └─► Retorna dados financeiros (JSON)
    │       │       │
    │       │       └─► Normaliza dados
    │       │
    │       └─► FinancialData.create()
    │               │
    │               └─► Salva no banco de dados
    │
    └─► Log de execução
```

#### Agente Pedro (Análise de Sentimento)

```
Início (Schedule: A cada hora)
    │
    ├─► Busca ações ativas (StockSymbol::active()->get())
    │
    ├─► Para cada ação:
    │       │
    │       ├─► NewsAnalysisService.searchNews(symbol, companyName)
    │       │       │
    │       │       ├─► Chama News API
    │       │       │       │
    │       │       │       └─► Retorna notícias (Array)
    │       │       │
    │       │       └─► Processa notícias
    │       │
    │       ├─► NewsAnalysisService.analyzeSentiment(articles)
    │       │       │
    │       │       └─► Analisa sentimento
    │       │               │
    │       │               └─► Retorna análise (Array)
    │       │
    │       └─► SentimentAnalysis.create()
    │               │
    │               └─► Salva no banco de dados
    │
    └─► Log de execução
```

#### Agente Key (Geração de Conteúdo)

```
Início (Schedule: A cada 30 minutos)
    │
    ├─► Busca ações com dados recentes (últimas 24h)
    │       │
    │       └─► StockSymbol::active()
    │               ->whereHas('financialData', ...)
    │               ->whereHas('sentimentAnalyses', ...)
    │
    ├─► Para cada ação:
    │       │
    │       ├─► Busca FinancialData (último)
    │       │       │
    │       │       └─► FinancialData::latest('collected_at')->first()
    │       │
    │       ├─► Busca SentimentAnalysis (última)
    │       │       │
    │       │       └─► SentimentAnalysis::latest('analyzed_at')->first()
    │       │
    │       ├─► Prepara dados de entrada
    │       │       │
    │       │       └─► JSON: { financial: {...}, sentiment: {...} }
    │       │
    │       ├─► LLMService.generateArticle()
    │       │       │
    │       │       ├─► Chama Python Script (run_llm.py)
    │       │       │       │
    │       │       │       ├─► Formata dados (llm_utils.py)
    │       │       │       │
    │       │       │       └─► Gera artigo (generate_article_content)
    │       │       │
    │       │       └─► Retorna { title, content }
    │       │
    │       ├─► Extrai recomendação do conteúdo
    │       │
    │       └─► Article.create()
    │               │
    │               └─► Salva no banco de dados (status: pendente_revisao)
    │
    └─► Log de execução
```

#### Agente PublishNotify (Notificação)

```
Início (Schedule: A cada 15 minutos)
    │
    ├─► Busca artigos pendentes não notificados
    │       │
    │       └─► Article::pendingReview()->notNotified()->get()
    │
    ├─► Prepara email com lista de artigos
    │
    ├─► Envia email para revisores
    │       │
    │       └─► Mail::send('emails.articles-pending-review', ...)
    │
    ├─► Marca artigos como notificados
    │       │
    │       └─► Article::update(['notified_at' => now()])
    │
    └─► Log de execução
```

#### Agente Cleanup (Limpeza)

```
Início (Schedule: Diário às 03:00)
    │
    ├─► Limpa logs antigos (>30 dias)
    │
    ├─► Limpa arquivos temporários (>30 dias)
    │
    ├─► Limpa caches antigos
    │
    ├─► Limpa dados financeiros antigos (>90 dias)
    │
    ├─► Limpa análises de sentimento antigas (>90 dias)
    │
    └─► Log de execução
```

---

## 🔒 Segurança e Compliance

### Autenticação

- **JWT (JSON Web Tokens)**: Autenticação de usuários
- **Middleware JWTToken**: Validação de tokens
- **Rate Limiting**: Limite de requisições por minuto

### Autorização

- **Policies**: Controle de acesso granular
  - `ArticlePolicy`: Aprovar/reprovar/publicar artigos
  - `StockSymbolPolicy`: Gerenciar ações monitoradas
- **Gates**: Permissões customizadas
  - `execute-agent`: Executar agentes
  - `review-articles`: Revisar artigos
  - `manage-stock-symbols`: Gerenciar ações

### Validação

- **Form Requests**: Validação de dados de entrada
  - `StockSymbolRequest`: Validação de ações
  - `ArticleRequest`: Validação de artigos

### Logging

- **Logs dedicados por agente**: Rastreamento de execuções
- **Logs de erro**: Tratamento de exceções
- **Auditoria**: Registro de ações importantes

### Compliance

- **Aviso Legal**: Limitação de recomendações explícitas de investimento
- **Transparência**: Deixa claro quando a IA contribuiu e quando houve revisão humana
- **Auditoria**: Registro de todas as fontes de dados

---

## 🚀 Deploy e Infraestrutura

### Containerização

- **Docker**: Containerização da aplicação
- **Docker Compose**: Orquestração de containers
  - `app`: Aplicação Laravel (PHP 8.0 + Nginx)
  - `db`: Banco de dados MySQL 5.7
  - `llm`: Serviço LLM (Python 3.9)

### Serviços

- **Nginx**: Servidor web
- **PHP-FPM**: Processador PHP
- **MySQL**: Banco de dados
- **Python**: Serviço LLM

### Configuração

- **.env**: Variáveis de ambiente
- **docker-compose.yaml**: Configuração de containers
- **Dockerfile**: Build da aplicação
- **DockerConfig/**: Configurações do Docker

### Monitoramento

- **Logs**: Logs dedicados por agente
- **Health Checks**: Verificação de saúde dos serviços
- **Métricas**: Métricas de execução (futuro)

---

## 📊 Diagrama de Sequência

### Fluxo Completo: Coleta → Análise → Geração → Revisão

```
Cliente          API          Agente Júlia    OpenAI API    Database
  │              │                 │              │            │
  │              │                 │              │            │
  │──Schedule───►│                 │              │            │
  │              │──Executa───────►│              │            │
  │              │                 │──Request────►│            │
  │              │                 │◄─Response────│            │
  │              │                 │──Save───────►│            │
  │              │                 │◄─────────────│            │
  │              │◄─Success────────│              │            │
  │              │                 │              │            │
  │              │                 │              │            │
  │──Schedule───►│                 │              │            │
  │              │──Executa───────►│ (Agente Pedro)            │
  │              │                 │──Request────►│ (News API) │
  │              │                 │◄─Response────│            │
  │              │                 │──Save───────►│            │
  │              │                 │◄─────────────│            │
  │              │◄─Success────────│              │            │
  │              │                 │              │            │
  │──Schedule───►│                 │              │            │
  │              │──Executa───────►│ (Agente Key) │            │
  │              │                 │──Read───────►│            │
  │              │                 │◄─────────────│            │
  │              │                 │──Request────►│ (Python)   │
  │              │                 │◄─Response────│            │
  │              │                 │──Save───────►│            │
  │              │                 │◄─────────────│            │
  │              │◄─Success────────│              │            │
  │              │                 │              │            │
  │──Schedule───►│                 │              │            │
  │              │──Executa───────►│ (PublishNotify)           │
  │              │                 │──Read───────►│            │
  │              │                 │◄─────────────│            │
  │              │                 │──Email──────►│ (Mail)     │
  │              │                 │──Update─────►│            │
  │              │                 │◄─────────────│            │
  │              │◄─Success────────│              │            │
  │              │                 │              │            │
  │──GET────────►│                 │              │            │
  │              │──Read───────────┼─────────────►│            │
  │              │◄────────────────┼──────────────│            │
  │◄─Response────│                 │              │            │
  │              │                 │              │            │
  │──POST───────►│                 │              │            │
  │ (Approve)    │──Update─────────┼─────────────►│            │
  │              │◄────────────────┼──────────────│            │
  │◄─Response────│                 │              │            │
```

---

## 📈 Métricas e Monitoramento

### Métricas de Agentes

- **Agente Júlia**: Número de ações coletadas, taxa de sucesso, tempo de execução
- **Agente Pedro**: Número de ações analisadas, taxa de sucesso, tempo de execução
- **Agente Key**: Número de artigos gerados, taxa de sucesso, tempo de execução
- **Agente PublishNotify**: Número de notificações enviadas, taxa de sucesso
- **Agente Cleanup**: Itens removidos, espaço liberado

### Logs

- **Logs por agente**: `storage/logs/agent_*.log`
- **Logs de erro**: `storage/logs/laravel.log`
- **Logs de API**: Logs de requisições HTTP

### Health Checks

- **Status dos agentes**: `/api/agents/status`
- **Status do banco**: Verificação de conexão
- **Status dos serviços**: Verificação de APIs externas

---

## 🔮 Melhorias Futuras

### Funcionalidades

- **Dashboard**: Interface web para monitoramento
- **Notificações em tempo real**: WebSockets para notificações
- **Análise de tendências**: Análise de tendências de mercado
- **Recomendações personalizadas**: Recomendações baseadas em perfil do usuário
- **Integração com mais fontes**: Integração com mais APIs de dados financeiros

### Performance

- **Cache**: Cache de dados frequentes
- **Queue**: Processamento assíncrono com filas
- **Otimização de queries**: Otimização de consultas ao banco
- **CDN**: CDN para assets estáticos

### Segurança

- **Rate Limiting avançado**: Rate limiting por usuário/IP
- **Criptografia**: Criptografia de dados sensíveis
- **Backup**: Backup automático do banco de dados
- **Monitoramento de segurança**: Monitoramento de ameaças

---

## 📚 Referências

### Documentação

- **Laravel**: https://laravel.com/docs
- **OpenAI API**: https://platform.openai.com/docs
- **News API**: https://newsapi.org/docs
- **Docker**: https://docs.docker.com

### Arquivos do Projeto

- **README.MD**: Documentação principal
- **INICIALIZACAO_COMPLETA.md**: Guia de inicialização
- **GUIA_EXECUCAO.md**: Guia de execução
- **EXECUTAR_TESTES.md**: Guia de testes

---

**Arquitetura completa documentada! 🎉**

