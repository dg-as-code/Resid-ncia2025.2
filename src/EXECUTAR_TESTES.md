# 🧪 Como Executar os Testes

Este guia explica como executar os testes automatizados do sistema de agentes de IA.

## 📋 Pré-requisitos

- Docker e Docker Compose instalados
- Containers do projeto rodando

## 🐳 Executar Testes via Docker

### 1. Executar Todos os Testes

```bash
# Via Docker Compose
docker-compose exec app php artisan test

# Ou diretamente no container
docker exec -it laravel_app php artisan test
```

### 2. Executar Testes Específicos

```bash
# Testes unitários
docker-compose exec app php artisan test --testsuite=Unit

# Testes de funcionalidade
docker-compose exec app php artisan test --testsuite=Feature

# Teste específico
docker-compose exec app php artisan test tests/Unit/Services/YahooFinanceServiceTest.php

# Método específico
docker-compose exec app php artisan test --filter it_can_get_quote_for_a_symbol
```

### 3. Com Cobertura de Código

```bash
docker-compose exec app php artisan test --coverage
```

### 4. Com Output Detalhado

```bash
# Verbose
docker-compose exec app php artisan test --verbose

# Com stop on failure
docker-compose exec app php artisan test --stop-on-failure
```

## 📊 Testes Disponíveis

### Testes Unitários

#### Services:
- `YahooFinanceServiceTest.php` - Testa integração com OpenAI
- `NewsAnalysisServiceTest.php` - Testa análise de notícias
- `LLMServiceTest.php` - Testa geração de conteúdo LLM

#### Commands:
- `AgentJuliaFetchTest.php` - Testa Agente Júlia
- `AgentKeyComposeTest.php` - Testa Agente Key

### Testes de Funcionalidade

- `AgentsApiTest.php` - Testa endpoints dos agentes
- `StockSymbolsApiTest.php` - Testa endpoints de ações
- `ArticlesApiTest.php` - Testa endpoints de artigos

## 🔧 Configuração

### phpunit.xml

O arquivo `phpunit.xml` já está configurado com:
- Ambiente de teste (`APP_ENV=testing`)
- Cache driver (`array`)
- Queue driver (`sync`)
- Mail driver (`array`)

### Banco de Dados de Teste

Os testes usam `RefreshDatabase` que:
- Cria um banco de dados temporário
- Executa migrações automaticamente
- Limpa dados após cada teste

## 🚀 Execução Local (Sem Docker)

Se você tiver PHP instalado localmente:

```bash
cd src
composer install
php artisan test
```

## 📝 Exemplos de Execução

### Executar Testes de um Agente Específico

```bash
# Testes do Agente Júlia
docker-compose exec app php artisan test tests/Unit/Commands/AgentJuliaFetchTest.php

# Testes do Agente Key
docker-compose exec app php artisan test tests/Unit/Commands/AgentKeyComposeTest.php
```

### Executar Testes de uma API Específica

```bash
# Testes da API de Agentes
docker-compose exec app php artisan test tests/Feature/AgentsApiTest.php

# Testes da API de Artigos
docker-compose exec app php artisan test tests/Feature/ArticlesApiTest.php
```

### Executar Testes com Filtros

```bash
# Apenas testes que contêm "openai" no nome
docker-compose exec app php artisan test --filter openai

# Apenas testes que contêm "agent" no nome
docker-compose exec app php artisan test --filter agent
```

## 🐛 Troubleshooting

### Erro: "Database does not exist"

```bash
# Criar banco de teste
docker-compose exec app php artisan db:create --database=testing
```

### Erro: "Migration failed"

```bash
# Executar migrações
docker-compose exec app php artisan migrate --env=testing
```

### Erro: "Class not found"

```bash
# Recarregar autoloader
docker-compose exec app composer dump-autoload
```

## 📈 Cobertura de Código

Para verificar cobertura de código:

```bash
docker-compose exec app php artisan test --coverage

# Com HTML report
docker-compose exec app php artisan test --coverage-html coverage/
```

## ✅ Checklist de Testes

Antes de fazer commit, execute:

```bash
# 1. Todos os testes
docker-compose exec app php artisan test

# 2. Testes unitários
docker-compose exec app php artisan test --testsuite=Unit

# 3. Testes de funcionalidade
docker-compose exec app php artisan test --testsuite=Feature

# 4. Com cobertura
docker-compose exec app php artisan test --coverage
```

## 🎯 Comandos Rápidos

```bash
# Testes rápidos (sem coverage)
docker-compose exec app php artisan test

# Testes completos (com coverage)
docker-compose exec app php artisan test --coverage

# Testes específicos
docker-compose exec app php artisan test --filter nome_do_teste
```

---

**Execute os testes regularmente para garantir qualidade! 🧪**

