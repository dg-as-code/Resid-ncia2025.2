# 📋 Guia de Testes do Sistema

Este documento descreve a estrutura de testes do sistema de agentes de IA para análise financeira.

## 📁 Estrutura de Testes

```
tests/
├── Feature/          # Testes de integração (API, rotas, controllers)
│   ├── AnalysisApiTest.php
│   ├── ArticlesApiTest.php
│   ├── AgentsApiTest.php
│   └── StockSymbolsApiTest.php
│
└── Unit/             # Testes unitários (Services, Jobs, Commands, Models)
    ├── Commands/
    │   ├── AgentJuliaFetchTest.php
    │   ├── AgentPedroAnalyzeTest.php
    │   ├── AgentPublishNotifyTest.php
    │   ├── AgentCleanupTest.php
    │   └── AgentKeyComposeTest.php
    │
    ├── Jobs/
    │   ├── FetchFinancialDataJobTest.php
    │   ├── AnalyzeMarketSentimentJobTest.php
    │   └── DraftArticleJobTest.php
    │
    ├── Models/
    │   └── AnalysisTest.php
    │
    └── Services/
        ├── YahooFinanceServiceTest.php
        ├── LLMServiceTest.php
        └── NewsAnalysisServiceTest.php
```

## 🧪 Tipos de Testes

### Feature Tests (Testes de Integração)

Testam o comportamento completo do sistema através de requisições HTTP:

- **AnalysisApiTest**: Testa endpoints de análise
  - Criação de análises
  - Listagem e filtros
  - Validação de dados
  - Autenticação

- **ArticlesApiTest**: Testa endpoints de artigos
  - Listagem
  - Aprovação/Reprovação
  - Publicação

- **AgentsApiTest**: Testa execução de agentes via API

- **StockSymbolsApiTest**: Testa CRUD de símbolos de ações

### Unit Tests (Testes Unitários)

Testam componentes isolados:

#### Commands (Comandos Artisan)
- **AgentJuliaFetchTest**: Testa coleta de dados financeiros
- **AgentPedroAnalyzeTest**: Testa análise de sentimento
- **AgentPublishNotifyTest**: Testa notificações
- **AgentCleanupTest**: Testa limpeza do sistema

#### Jobs (Trabalhos em Fila)
- **FetchFinancialDataJobTest**: Testa job de coleta de dados
- **AnalyzeMarketSentimentJobTest**: Testa job de análise de sentimento
- **DraftArticleJobTest**: Testa job de geração de artigos

#### Services (Serviços)
- **YahooFinanceServiceTest**: Testa serviço de dados financeiros
- **LLMServiceTest**: Testa serviço de LLM
- **NewsAnalysisServiceTest**: Testa serviço de notícias

#### Models (Modelos)
- **AnalysisTest**: Testa relacionamentos e scopes do modelo Analysis

## 🚀 Como Executar os Testes

### Executar Todos os Testes

```bash
cd src
php artisan test
```

ou

```bash
cd src
vendor/bin/phpunit
```

### Executar Testes Específicos

```bash
# Apenas testes de Feature
php artisan test --testsuite=Feature

# Apenas testes Unitários
php artisan test --testsuite=Unit

# Um arquivo específico
php artisan test tests/Feature/AnalysisApiTest.php

# Um método específico
php artisan test --filter it_can_request_new_analysis
```

### Com Cobertura de Código

```bash
php artisan test --coverage
```

## 📊 Cobertura de Testes

### Componentes Testados

✅ **Controllers**
- AnalysisController
- ArticleController
- StockSymbolController
- AgentController

✅ **Jobs**
- FetchFinancialDataJob
- AnalyzeMarketSentimentJob
- DraftArticleJob
- NotifyReviewerJob

✅ **Commands**
- AgentJuliaFetch
- AgentPedroAnalyze
- AgentPublishNotify
- AgentCleanup
- AgentKeyCompose

✅ **Services**
- YahooFinanceService
- LLMService
- NewsAnalysisService

✅ **Models**
- Analysis (relacionamentos e scopes)
- StockSymbol
- Article
- FinancialData
- SentimentAnalysis

## 🔧 Configuração de Testes

### Ambiente de Teste

O arquivo `phpunit.xml` configura:
- Ambiente: `testing`
- Cache: `array` (em memória)
- Queue: `sync` (síncrono)
- Mail: `array` (fake)

### Factories

Factories disponíveis:
- `UserFactory`
- `StockSymbolFactory`
- `AnalysisFactory`
- `FinancialDataFactory`
- `SentimentAnalysisFactory`
- `ArticleFactory`

### Mocks e Fakes

Os testes usam:
- `Bus::fake()` - Para testar jobs sem executá-los
- `Mail::fake()` - Para testar emails sem enviá-los
- `Http::fake()` - Para mockar requisições HTTP
- `Mockery` - Para mockar serviços

## 📝 Escrevendo Novos Testes

### Estrutura Básica

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeuTeste extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_does_something()
    {
        // Arrange
        $data = ['key' => 'value'];

        // Act
        $response = $this->postJson('/api/endpoint', $data);

        // Assert
        $response->assertStatus(200);
    }
}
```

### Boas Práticas

1. **Use RefreshDatabase**: Limpa o banco entre testes
2. **Use Factories**: Crie dados de teste consistentes
3. **Use Fakes**: Para serviços externos (HTTP, Mail, Queue)
4. **Nomeie bem**: Use `it_does_something` ou `test_it_does_something`
5. **AAA Pattern**: Arrange, Act, Assert
6. **Um teste, uma coisa**: Cada teste deve verificar uma funcionalidade

## 🐛 Troubleshooting

### Erros Comuns

1. **Database não encontrado**
   - Verifique se o banco de testes está configurado no `.env.testing`

2. **Factory não encontrada**
   - Execute `php artisan make:factory NomeFactory`

3. **Mock não funciona**
   - Verifique se está usando `$this->app->instance()` para injetar o mock

4. **Jobs não executam**
   - Use `Bus::fake()` para testar sem executar
   - Use `Queue::fake()` se necessário

## 📚 Recursos

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](https://docs.mockery.io/)

