<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Este arquivo armazena credenciais para serviços de terceiros como
    | Mailgun, Postmark, AWS e outros. Também inclui configurações para
    | serviços utilizados pelos agentes de IA.
    |
    | Serviços dos Agentes:
    | - Yahoo Finance: dados financeiros (Agente Júlia)
    | - News API: notícias e análise de sentimento (Agente Pedro)
    | - LLM/Python: geração de conteúdo (Agente Key)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Serviços de Email
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Serviços dos Agentes de IA
    |--------------------------------------------------------------------------
    */

    /*
    | OpenAI API (Agente Júlia)
    | 
    | Usa OpenAI para buscar dados financeiros de ações.
    | O serviço mantém o nome YahooFinanceService para compatibilidade.
    | 
    | Nota: OpenAI pode não ter dados atualizados em tempo real.
    | Para produção, considere usar APIs financeiras especializadas.
    */
    'yahoo_finance' => [
        // Mantido para compatibilidade, mas não é mais usado
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'rate_limit' => env('OPENAI_RATE_LIMIT', 60), // requisições por minuto
    ],

    /*
    | News API (Agente Pedro)
    | 
    | API para buscar notícias e análise de sentimento.
    | Obtenha sua chave em: https://newsapi.org/
    */
    'news_api' => [
        'api_key' => env('NEWS_API_KEY'),
        'base_url' => env('NEWS_API_BASE_URL', 'https://newsapi.org/v2'),
        'timeout' => env('NEWS_API_TIMEOUT', 10),
        'rate_limit' => env('NEWS_API_RATE_LIMIT', 100), // requisições por dia (plano gratuito)
    ],

    /*
    | LLM / Python Integration (Agente Key)
    | 
    | Configurações para integração com modelos de linguagem.
    | Pode usar APIs externas (OpenAI, Anthropic) ou scripts Python locais.
    */
    'llm' => [
        'provider' => env('LLM_PROVIDER', 'python'), // 'python', 'openai', 'anthropic'
        'python_path' => env('PYTHON_PATH', 'python3'),
        'python_script_path' => env('LLM_SCRIPT_PATH', 'llm/scripts/run_llm.py'),
        'timeout' => env('LLM_TIMEOUT', 60), // segundos
        
        // OpenAI (se usar OpenAI API)
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        
        // Anthropic (se usar Claude API)
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificações (Agente PublishNotify)
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'reviewer_email' => env('REVIEWER_EMAIL'),
        'admin_email' => env('ADMIN_EMAIL'),
        'slack_webhook' => env('SLACK_WEBHOOK_URL'),
    ],

];
