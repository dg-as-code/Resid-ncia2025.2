# LLM - Sistema de Geração de Conteúdo

Este diretório contém scripts e utilitários para integração com modelos de linguagem (LLM) usados pelo **Agente Key** para gerar matérias financeiras.

## Estrutura

```
llm/
├── scripts/
│   ├── run_llm.py          # Script principal para executar LLM
│   └── prepare_inputs.py   # Script para preparar dados de entrada
├── utils/
│   └── llm_utils.py        # Funções utilitárias para LLM
├── models/
│   └── README.md           # Documentação sobre modelos
└── README.md               # Este arquivo
```

## Uso

### Executar LLM via PHP (Laravel)

O script `run_llm.py` é chamado automaticamente pelo `LLMService` do Laravel:

```php
$service = new \App\Services\LLMService();
$article = $service->generateArticle($financialData, $sentimentData, 'PETR4');
```

### Executar diretamente via Python

```bash
# Formato de entrada: JSON string
python run_llm.py '{"symbol":"PETR4","financial":{"price":30.50},"sentiment":{"sentiment":"positive"}}'
```

### Preparar dados de entrada

```bash
python prepare_inputs.py input.json output.json
```

## Formato de Dados

### Entrada (JSON)

```json
{
  "symbol": "PETR4",
  "financial": {
    "price": 30.50,
    "previous_close": 30.00,
    "change": 0.50,
    "change_percent": 1.67,
    "volume": 50000000,
    "market_cap": 200000000000,
    "pe_ratio": 8.5,
    "dividend_yield": 5.2,
    "high_52w": 35.00,
    "low_52w": 25.00
  },
  "sentiment": {
    "sentiment": "positive",
    "sentiment_score": 0.65,
    "news_count": 15,
    "positive_count": 10,
    "negative_count": 3,
    "neutral_count": 2,
    "trending_topics": "crescimento, lucro, expansão"
  }
}
```

### Saída (JSON)

```json
{
  "title": "Análise PETR4: Mercado em alta - R$ 30.50",
  "content": "## Análise de PETR4\n\n### Dados Financeiros\n\n..."
}
```

## Configuração

Configure as variáveis de ambiente no `.env`:

```env
LLM_PROVIDER=python
PYTHON_PATH=python3
LLM_SCRIPT_PATH=llm/scripts/run_llm.py
LLM_TIMEOUT=60
```

## Integração com APIs Externas

Para usar OpenAI ou Anthropic, configure:

```env
LLM_PROVIDER=openai
OPENAI_API_KEY=sua_chave
OPENAI_MODEL=gpt-3.5-turbo
```

## Dependências Python

Instale as dependências necessárias:

```bash
pip install python-dotenv
```

## Notas

- Por padrão, o sistema usa templates simples para gerar conteúdo
- Para usar LLM real, implemente a integração em `generate_article_content()` em `llm_utils.py`
- O sistema é compatível com OpenAI, Anthropic ou modelos locais

