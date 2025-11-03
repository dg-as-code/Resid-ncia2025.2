# Modelos LLM

Este diretório é destinado para armazenar modelos de linguagem (LLM) usados pelo **Agente Key** para gerar matérias financeiras.

## Estrutura de Modelos

### Modelos Suportados

1. **Python Scripts Locais**
   - Modelos executados via scripts Python locais
   - Caminho: `llm/scripts/run_llm.py`

2. **OpenAI API**
   - Modelos: GPT-3.5-turbo, GPT-4, etc.
   - Configuração via `config/services.php`

3. **Anthropic API**
   - Modelos: Claude 3 Sonnet, etc.
   - Configuração via `config/services.php`

4. **Modelos Locais**
   - Modelos treinados localmente
   - Armazenar neste diretório quando disponíveis

## Arquitetura de Modelos

### Template Atual (Fallback)

O sistema atual usa um template simples para gerar conteúdo. Para usar um LLM real:

1. Implemente a função `generate_article_content()` em `llm_utils.py`
2. Ou configure integração com OpenAI/Anthropic em `LLMService.php`

### Exemplo de Integração

```python
# llm_utils.py
def generate_article_content(formatted_data):
    # Integração com OpenAI
    import openai
    openai.api_key = os.getenv('OPENAI_API_KEY')
    
    prompt = f"Gere um artigo financeiro sobre {formatted_data['symbol']}..."
    response = openai.ChatCompletion.create(
        model="gpt-3.5-turbo",
        messages=[{"role": "user", "content": prompt}]
    )
    
    return {
        'title': response['title'],
        'content': response['content']
    }
```

## Processo de Treinamento (Futuro)

Se você planeja treinar modelos customizados:

- **Dataset**: Dados históricos de matérias financeiras
- **Hyperparâmetros**: A definir conforme modelo escolhido
- **Técnicas**: Fine-tuning, transfer learning, etc.

## Instruções de Uso

### Via Laravel (Recomendado)

```php
$service = new \App\Services\LLMService();
$article = $service->generateArticle($financialData, $sentimentData, 'PETR4');
```

### Via Python Direto

```bash
python run_llm.py '{"symbol":"PETR4",...}'
```

## Informações Adicionais

Para mais detalhes sobre scripts e utilitários, consulte:
- `src/llm/README.md` - Documentação principal
- `src/llm/utils/llm_utils.py` - Funções utilitárias
- `src/app/Services/LLMService.php` - Service do Laravel