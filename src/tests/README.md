# Testes - Sistema de Agentes de IA

Este diretório contém todos os testes automatizados do sistema de agentes de IA.

## Estrutura

```
tests/
├── Unit/              # Testes unitários
│   ├── Services/      # Testes dos serviços
│   └── Commands/      # Testes dos comandos (agentes)
├── Feature/           # Testes de funcionalidade (API)
├── TestCase.php       # Classe base para testes
├── CreatesApplication.php  # Trait para criar aplicação
└── README.md          # Este arquivo
```

## Tipos de Testes

### Testes Unitários (`tests/Unit/`)

Testam componentes isolados:

- **Services**: `YahooFinanceService`, `NewsAnalysisService`, `LLMService`
- **Commands**: Comandos dos agentes (Júlia, Pedro, Key, etc.)
- **Models**: Modelos Eloquent

### Testes de Funcionalidade (`tests/Feature/`)

Testam funcionalidades completas:

- **API Endpoints**: Rotas da API dos agentes
- **Fluxos Completos**: Fluxos de aprovação de artigos, etc.

## Executando Testes

### Executar Todos os Testes

```bash
# Via Artisan
php artisan test

# Via PHPUnit
./vendor/bin/phpunit
```

### Executar Testes Específicos

```bash
# Testes unitários
php artisan test --testsuite=Unit

# Testes de funcionalidade
php artisan test --testsuite=Feature

# Teste específico
php artisan test tests/Unit/Services/YahooFinanceServiceTest.php

# Método específico
php artisan test --filter it_can_get_quote_for_a_symbol
```

### Com Cobertura de Código

```bash
php artisan test --coverage
```

## Testes dos Agentes

### Agente Júlia (Coleta de Dados Financeiros)

```bash
php artisan test tests/Unit/Commands/AgentJuliaFetchTest.php
```

**Testes:**
- Coleta dados para símbolo específico
- Coleta dados para todas as ações padrão
- Tratamento de erros da API

### Agente Pedro (Análise de Sentimento)

```bash
php artisan test tests/Unit/Services/NewsAnalysisServiceTest.php
```

**Testes:**
- Busca de notícias
- Análise de sentimento
- Tratamento de erros

### Agente Key (Geração de Matérias)

```bash
php artisan test tests/Unit/Commands/AgentKeyComposeTest.php
```

**Testes:**
- Geração de artigos
- Validação de dados necessários
- Tratamento de erros

## Testes da API

### Endpoints dos Agentes

```bash
php artisan test tests/Feature/AgentsApiTest.php
```

**Testes:**
- Status dos agentes
- Execução dos agentes
- Rate limiting

### Endpoints de Ações

```bash
php artisan test tests/Feature/StockSymbolsApiTest.php
```

**Testes:**
- Listagem de ações
- Criação de ações (com autenticação)
- Visualização de ações

### Endpoints de Artigos

```bash
php artisan test tests/Feature/ArticlesApiTest.php
```

**Testes:**
- Listagem de artigos
- Aprovação de artigos
- Reprovação de artigos
- Publicação de artigos

## Configuração

### phpunit.xml

O arquivo `phpunit.xml` configura:
- Ambiente de teste (`APP_ENV=testing`)
- Cache driver (`array`)
- Queue driver (`sync`)
- Mail driver (`array`)

### Banco de Dados de Teste

Por padrão, os testes usam `RefreshDatabase` que:
- Cria um banco de dados temporário
- Executa migrações
- Limpa dados após cada teste

## Mocking e Stubs

### HTTP Requests

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.example.com/*' => Http::response(['data' => 'test'], 200),
]);
```

### Process Commands

```php
use Illuminate\Support\Facades\Process;

Process::fake([
    '*' => Process::result(exitCode: 0, output: 'Success'),
]);
```

### Logs

```php
use Illuminate\Support\Facades\Log;

Log::shouldReceive('info')
    ->once()
    ->with('Expected message');
```

## Boas Práticas

1. **Use Factories**: Para criar dados de teste
2. **Use RefreshDatabase**: Para limpar banco entre testes
3. **Mock External Services**: Não faça chamadas reais para APIs externas
4. **Teste Comportamentos**: Não teste implementação
5. **Nomes Descritivos**: Use `it_can_do_something` pattern
6. **Arrange-Act-Assert**: Organize testes em 3 fases

## Exemplo de Teste

```php
<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_approve_an_article()
    {
        // Arrange
        $article = Article::factory()->create([
            'status' => 'pendente_revisao',
        ]);

        // Act
        $response = $this->postJson("/api/articles/{$article->id}/approve");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => 'aprovado',
        ]);
    }
}
```

## Troubleshooting

### Erro: "Database does not exist"

```bash
# Criar banco de teste
php artisan db:create --database=testing
```

### Erro: "Migration failed"

```bash
# Executar migrações
php artisan migrate --env=testing
```

### Erro: "Class not found"

```bash
# Recarregar autoloader
composer dump-autoload
```

## Cobertura de Código

Para verificar cobertura de código:

```bash
php artisan test --coverage

# Ou com Xdebug
./vendor/bin/phpunit --coverage-html coverage/
```

## CI/CD

Para executar testes em CI/CD:

```yaml
# .github/workflows/tests.yml
- name: Run Tests
  run: php artisan test
```

## Notas

- Testes são executados em ambiente isolado
- Dados são limpos após cada teste
- Mocks são usados para APIs externas
- Factories são usados para criar dados de teste

---

**Execute os testes regularmente para garantir qualidade do código! 🧪**

