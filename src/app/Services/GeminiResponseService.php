<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço para geração de respostas usando Google Gemini API
 * 
 * Orquestra chamadas à API Gemini para gerar conteúdo de forma padronizada.
 * Suporta diferentes tipos de geração: artigos, análises, resumos, etc.
 */
class GeminiResponseService
{
    protected $apiKey;
    protected $baseUrl;
    protected $model;
    protected $timeout;
    protected $defaultTemperature;
    protected $defaultMaxTokens;

    public function __construct()
    {
        $config = config('services.llm.gemini', []);
        
        $this->apiKey = $config['api_key'] ?? env('GEMINI_API_KEY');
        $this->baseUrl = $config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta';
        // Atualiza modelo padrão para versão mais recente (gemini-1.5-flash ou gemini-1.5-pro)
        $this->model = $config['model'] ?? env('GEMINI_MODEL', 'gemini-1.5-flash');
        $this->timeout = $config['timeout'] ?? 60;
        $this->defaultTemperature = $config['temperature'] ?? 0.7;
        $this->defaultMaxTokens = $config['max_tokens'] ?? 2048;
    }

    /**
     * Gera resposta genérica usando Gemini
     * 
     * @param string $prompt Prompt para o modelo
     * @param array $options Opções de geração (temperature, max_tokens, response_format, etc.)
     * @return array Resposta com 'content', 'metadata' e 'raw_response'
     */
    public function generateResponse(string $prompt, array $options = []): array
    {
        try {
            if (!$this->isConfigured()) {
                throw new \Exception('Gemini API não está configurada. Verifique GEMINI_API_KEY no .env');
            }

            $temperature = $options['temperature'] ?? $this->defaultTemperature;
            $maxTokens = $options['max_tokens'] ?? $this->defaultMaxTokens;
            $responseFormat = $options['response_format'] ?? 'text'; // 'text' ou 'json'
            $systemInstruction = $options['system_instruction'] ?? null;
            $safetySettings = $options['safety_settings'] ?? null;

            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ];
            
            // Nota: Para habilitar busca na web (Google Search) no Gemini, é necessário:
            // 1. Usar modelo Gemini 1.5 Pro ou superior
            // 2. Habilitar Google Search Extension na API
            // 3. Configurar groundingConfig com googleSearchRetrieval
            // Por enquanto, as instruções de busca na web estão no prompt

            // Adiciona formato de resposta JSON se solicitado
            if ($responseFormat === 'json') {
                $payload['generationConfig']['responseMimeType'] = 'application/json';
            }

            // Adiciona instrução do sistema se fornecida
            if ($systemInstruction) {
                $payload['systemInstruction'] = [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ];
            }

            // Adiciona configurações de segurança se fornecidas
            if ($safetySettings) {
                $payload['safetySettings'] = $safetySettings;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($url, $payload);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                $errorMessage = $errorData['error']['message'] ?? $errorBody;
                
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'response' => $errorBody,
                    'model' => $this->model,
                    'prompt_preview' => substr($prompt, 0, 100),
                ]);
                
                // Se for 404, sugere verificar o modelo
                if ($response->status() === 404) {
                    throw new \Exception("Gemini API: Modelo '{$this->model}' não encontrado. Verifique se o modelo está correto (gemini-1.5-flash, gemini-1.5-pro, etc). Erro: " . $errorMessage);
                }
                
                throw new \Exception("Gemini API error (HTTP {$response->status()}): " . $errorMessage);
            }

            $data = $response->json();

            // Verifica se há conteúdo na resposta
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $errorMessage = $data['error']['message'] ?? 'No content in response';
                Log::warning('Gemini: No content in response', [
                    'response' => $data,
                ]);
                throw new \Exception("Gemini: {$errorMessage}");
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Tenta decodificar JSON se o formato for JSON
            $parsedContent = $content;
            if ($responseFormat === 'json') {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $parsedContent = $decoded;
                } else {
                    // Se falhou, tenta extrair JSON de markdown code blocks
                    if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $content, $matches)) {
                        $jsonInBlock = json_decode(trim($matches[1]), true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonInBlock)) {
                            $parsedContent = $jsonInBlock;
                        }
                    }
                }
            }

            // Extrai metadados da resposta
            $metadata = [
                'model' => $this->model,
                'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
                'safety_ratings' => $data['candidates'][0]['safetyRatings'] ?? [],
                'usage_metadata' => $data['usageMetadata'] ?? null,
                'prompt_feedback' => $data['promptFeedback'] ?? null,
            ];

            return [
                'success' => true,
                'content' => $parsedContent,
                'raw_content' => $content,
                'metadata' => $metadata,
                'raw_response' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('GeminiResponseService error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null,
            ];
        }
    }

    /**
     * Gera artigo financeiro usando dados estruturados
     * 
     * @param array $financialData Dados financeiros
     * @param array $sentimentData Dados de sentimento
     * @param string $symbol Símbolo da ação
     * @param array $options Opções adicionais
     * @return array Artigo gerado com 'title' e 'content'
     */
    public function generateArticle(array $financialData, array $sentimentData, string $symbol, array $options = []): array
    {
        // Cache baseado nos dados de entrada (hash dos dados principais)
        // Cache por 1 hora (3600 segundos) - artigos similares não precisam ser regenerados
        $cacheKey = "article:" . md5(json_encode([
            $financialData['price'] ?? 0,
            $financialData['symbol'] ?? '',
            $financialData['change_percent'] ?? 0,
            $sentimentData['sentiment'] ?? 'neutral',
            $sentimentData['sentiment_score'] ?? 0,
            $symbol
        ]));
        
        return Cache::remember($cacheKey, 3600, function () use ($financialData, $sentimentData, $symbol, $options) {
            $prompt = $this->buildArticlePrompt($financialData, $sentimentData, $symbol);
            
            $defaultOptions = [
                'temperature' => 0.6, // Reduzido para mais objetividade, mas mantém criatividade
                'max_tokens' => 3072, // Aumentado para permitir análises mais aprofundadas
                'response_format' => 'text',
                'system_instruction' => 'Você é um jornalista financeiro veterano com mais de 15 anos de experiência. Sua missão é transformar dados técnicos em redação jornalística clara, objetiva, aprofundada e profissional. Você explica o significado dos dados, cria narrativas coesas e ajuda o leitor a entender o contexto completo da situação. Você tem acesso à busca na web para complementar informações quando necessário. Use essa funcionalidade para buscar dados atualizados sobre empresas, mercado financeiro e contexto relevante. Sempre integre informações encontradas de forma natural na narrativa jornalística.',
            ];

            $options = array_merge($defaultOptions, $options);

            $result = $this->generateResponse($prompt, $options);

            if (!$result['success']) {
                return [
                    'title' => "Análise {$symbol}",
                    'content' => "Erro ao gerar artigo: " . ($result['error'] ?? 'Erro desconhecido'),
                ];
            }

            // Parse do conteúdo gerado
            return $this->parseArticleResponse($result['content'], $symbol);
        });
    }

    /**
     * Gera análise de dados financeiros
     * 
     * @param array $data Dados financeiros
     * @param string $symbol Símbolo da ação
     * @return array Análise gerada
     */
    public function generateAnalysis(array $data, string $symbol): array
    {
        $prompt = "Analise os seguintes dados financeiros da ação {$symbol} e forneça uma análise detalhada:\n\n";
        $prompt .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $prompt .= "\n\nForneça uma análise estruturada em formato JSON com os campos: 'resumo', 'pontos_positivos', 'pontos_negativos', 'recomendacao'.";

        $result = $this->generateResponse($prompt, [
            'temperature' => 0.5,
            'response_format' => 'json',
        ]);

        if (!$result['success']) {
            return [
                'resumo' => "Erro ao gerar análise",
                'pontos_positivos' => [],
                'pontos_negativos' => [],
                'recomendacao' => 'Análise não disponível',
            ];
        }

        return is_array($result['content']) ? $result['content'] : json_decode($result['content'], true) ?? [];
    }

    /**
     * Gera resumo de texto longo
     * 
     * @param string $text Texto a ser resumido
     * @param int $maxLength Tamanho máximo do resumo
     * @return string Resumo gerado
     */
    public function generateSummary(string $text, int $maxLength = 200): string
    {
        $prompt = "Resuma o seguinte texto em no máximo {$maxLength} palavras, mantendo as informações mais importantes:\n\n{$text}";

        $result = $this->generateResponse($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);

        if (!$result['success']) {
            return substr($text, 0, $maxLength) . '...';
        }

        return $result['content'];
    }

    /**
     * Gera resposta com contexto de conversação
     * 
     * @param array $messages Array de mensagens no formato ['role' => 'user|assistant', 'content' => '...']
     * @param array $options Opções adicionais
     * @return array Resposta gerada
     */
    public function generateConversationalResponse(array $messages, array $options = []): array
    {
        $contents = [];
        
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            
            // Gemini usa 'user' e 'model' ao invés de 'assistant'
            $geminiRole = $role === 'assistant' ? 'model' : 'user';
            
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [
                    ['text' => $content]
                ]
            ];
        }

        try {
            if (!$this->isConfigured()) {
                throw new \Exception('Gemini API não está configurada');
            }

            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? $this->defaultTemperature,
                    'maxOutputTokens' => $options['max_tokens'] ?? $this->defaultMaxTokens,
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($url, $payload);

            if (!$response->successful()) {
                throw new \Exception("Gemini API error: " . $response->body());
            }

            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception("No content in response");
            }

            return [
                'success' => true,
                'content' => $data['candidates'][0]['content']['parts'][0]['text'],
                'metadata' => [
                    'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Gemini conversational error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null,
            ];
        }
    }

    /**
     * Verifica se o serviço está configurado
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Constrói prompt para geração de artigo financeiro
     * 
     * @param array $financialData
     * @param array $sentimentData
     * @param string $symbol
     * @return string
     */
    protected function buildArticlePrompt(array $financialData, array $sentimentData, string $symbol): string
    {
        $companyName = $financialData['company_name'] ?? $symbol;
        
        $prompt = "Você é um jornalista financeiro veterano com mais de 15 anos de experiência em cobertura de mercado de capitais, análise fundamentalista e redação de matérias financeiras para veículos de grande circulação. Seu estilo é claro, objetivo, preciso e aprofundado, transformando dados técnicos complexos em narrativas jornalísticas acessíveis e informativas.\n\n";
        
        $prompt .= "SUA MISSÃO COMO JORNALISTA:\n";
        $prompt .= "Transformar os dados brutos coletados em uma matéria jornalística profissional, aprofundada e objetiva. Você não apenas apresenta números, mas os contextualiza, explica seu significado e cria uma narrativa coesa que ajuda o leitor a entender o que está acontecendo com a empresa no mercado.\n\n";
        
        $prompt .= "CONTEXTO:\n";
        $prompt .= "Você está escrevendo uma matéria sobre a ação {$symbol} ({$companyName}) para um portal financeiro de credibilidade. A matéria será revisada por editores humanos antes da publicação e deve estar pronta para publicação após revisão.\n\n";
        
        $prompt .= "BUSCA DE INFORMAÇÕES ADICIONAIS:\n";
        $prompt .= "Você tem acesso à busca na web (Google Search) através do Gemini. Use essa funcionalidade para:\n";
        $prompt .= "- Buscar informações atualizadas sobre a empresa que não estão nos dados fornecidos\n";
        $prompt .= "- Verificar contexto de mercado e tendências recentes\n";
        $prompt .= "- Complementar dados financeiros com informações de fontes confiáveis\n";
        $prompt .= "- Enriquecer a análise com dados históricos e comparativos\n";
        $prompt .= "IMPORTANTE: Sempre integre informações encontradas na web de forma natural na narrativa, citando fontes quando apropriado. Use os dados dos agentes como base principal e informações da web como complemento.\n\n";
        
        $prompt .= "DADOS FINANCEIROS:\n";
        $prompt .= "- Preço atual: R$ " . ($financialData['price'] ?? 'N/A') . "\n";
        $prompt .= "- Fechamento anterior: R$ " . ($financialData['previous_close'] ?? 'N/A') . "\n";
        $prompt .= "- Variação: " . ($financialData['change'] ?? 0) . " (" . ($financialData['change_percent'] ?? 0) . "%)\n";
        $prompt .= "- Volume negociado: " . ($financialData['volume'] ?? 'N/A') . "\n";
        $prompt .= "- Capitalização de mercado: R$ " . ($financialData['market_cap'] ?? 'N/A') . "\n";
        $prompt .= "- P/L: " . ($financialData['pe_ratio'] ?? 'N/A') . "\n";
        $prompt .= "- Dividend Yield: " . ($financialData['dividend_yield'] ?? 'N/A') . "%\n";
        $prompt .= "- Máxima 52 semanas: R$ " . ($financialData['high_52w'] ?? 'N/A') . "\n";
        $prompt .= "- Mínima 52 semanas: R$ " . ($financialData['low_52w'] ?? 'N/A') . "\n\n";
        
        $prompt .= "ANÁLISE DE SENTIMENTO E PERCEPÇÃO DE MARCA (Agente Pedro):\n";
        $prompt .= "- Sentimento geral: " . ($sentimentData['sentiment'] ?? 'neutral') . "\n";
        $prompt .= "- Score de sentimento: " . ($sentimentData['sentiment_score'] ?? 0) . "\n";
        $prompt .= "- Notícias analisadas: " . ($sentimentData['news_count'] ?? 0) . "\n";
        $prompt .= "- Notícias positivas: " . ($sentimentData['positive_count'] ?? 0) . "\n";
        $prompt .= "- Notícias negativas: " . ($sentimentData['negative_count'] ?? 0) . "\n";
        $prompt .= "- Notícias neutras: " . ($sentimentData['neutral_count'] ?? 0) . "\n";
        $prompt .= "- Tópicos em destaque: " . ($sentimentData['trending_topics'] ?? 'N/A') . "\n";
        $prompt .= "- Fontes de notícias: " . (is_array($sentimentData['news_sources'] ?? null) ? implode(', ', $sentimentData['news_sources']) : ($sentimentData['news_sources'] ?? 'N/A')) . "\n";
        
        // Adiciona análise de mercado se disponível (do Agente Pedro)
        if (!empty($sentimentData['market_analysis'])) {
            $prompt .= "\nANÁLISE DE MERCADO (Agente Pedro):\n";
            $marketAnalysis = is_array($sentimentData['market_analysis']) 
                ? json_encode($sentimentData['market_analysis'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $sentimentData['market_analysis'];
            $prompt .= $marketAnalysis . "\n";
        }
        
        // Adiciona análise macroeconômica se disponível (do Agente Pedro)
        if (!empty($sentimentData['macroeconomic_analysis'])) {
            $prompt .= "\nANÁLISE MACROECONÔMICA (Agente Pedro):\n";
            $macroAnalysis = is_array($sentimentData['macroeconomic_analysis']) 
                ? json_encode($sentimentData['macroeconomic_analysis'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $sentimentData['macroeconomic_analysis'];
            $prompt .= $macroAnalysis . "\n";
        }
        
        // Adiciona insights estratégicos se disponíveis (do Agente Pedro)
        if (!empty($sentimentData['key_insights'])) {
            $prompt .= "\nINSIGHTS PRINCIPAIS (Agente Pedro):\n";
            $keyInsights = is_array($sentimentData['key_insights']) 
                ? json_encode($sentimentData['key_insights'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $sentimentData['key_insights'];
            $prompt .= $keyInsights . "\n";
        }
        
        if (!empty($sentimentData['actionable_insights']) || !empty($sentimentData['strategic_analysis'])) {
            $prompt .= "\nINSIGHTS ESTRATÉGICOS E ANÁLISE (Agente Pedro):\n";
            if (!empty($sentimentData['actionable_insights'])) {
                $insights = is_array($sentimentData['actionable_insights']) 
                    ? json_encode($sentimentData['actionable_insights'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $sentimentData['actionable_insights'];
                $prompt .= "Insights Acionáveis: " . $insights . "\n";
            }
            if (!empty($sentimentData['strategic_analysis'])) {
                $prompt .= "Análise Estratégica Completa: " . $sentimentData['strategic_analysis'] . "\n";
            }
        }
        
        // Adiciona métricas de marca e percepção se disponíveis (do Agente Pedro)
        if (!empty($sentimentData['brand_perception']) || !empty($sentimentData['engagement_metrics']) || !empty($sentimentData['investor_confidence'])) {
            $prompt .= "\nMÉTRICAS DE PERCEPÇÃO DE MARCA E COMPORTAMENTO (Agente Pedro):\n";
            if (!empty($sentimentData['brand_perception'])) {
                $brandPerception = is_array($sentimentData['brand_perception']) 
                    ? json_encode($sentimentData['brand_perception'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $sentimentData['brand_perception'];
                $prompt .= "Percepção da Marca: " . $brandPerception . "\n";
            }
            if (!empty($sentimentData['engagement_metrics'])) {
                $engagement = is_array($sentimentData['engagement_metrics']) 
                    ? json_encode($sentimentData['engagement_metrics'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $sentimentData['engagement_metrics'];
                $prompt .= "Métricas de Engajamento: " . $engagement . "\n";
            }
            if (!empty($sentimentData['investor_confidence'])) {
                $confidence = is_array($sentimentData['investor_confidence']) 
                    ? json_encode($sentimentData['investor_confidence'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $sentimentData['investor_confidence'];
                $prompt .= "Confiança do Investidor: " . $confidence . "\n";
            }
            if (!empty($sentimentData['sentiment_breakdown'])) {
                $breakdown = is_array($sentimentData['sentiment_breakdown']) 
                    ? json_encode($sentimentData['sentiment_breakdown'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $sentimentData['sentiment_breakdown'];
                $prompt .= "Detalhamento de Sentimento: " . $breakdown . "\n";
            }
        }
        
        // Adiciona dados digitais e comportamentais se disponíveis (de raw_data._analysis)
        if (!empty($sentimentData['raw_data']) && is_array($sentimentData['raw_data']) && !empty($sentimentData['raw_data']['_analysis'])) {
            $analysis = $sentimentData['raw_data']['_analysis'];
            if (!empty($analysis['digital_data']) || !empty($analysis['behavioral_data'])) {
                $prompt .= "\nDADOS DIGITAIS E COMPORTAMENTAIS (Agente Pedro):\n";
                if (!empty($analysis['digital_data'])) {
                    $digitalData = is_array($analysis['digital_data']) 
                        ? json_encode($analysis['digital_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : $analysis['digital_data'];
                    $prompt .= "Dados Digitais (Volume, Sentimento, Engajamento, Alcance): " . $digitalData . "\n";
                }
                if (!empty($analysis['behavioral_data'])) {
                    $behavioralData = is_array($analysis['behavioral_data']) 
                        ? json_encode($analysis['behavioral_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : $analysis['behavioral_data'];
                    $prompt .= "Dados Comportamentais (Intenções de Compra, Reclamações, Feedback, Avaliações): " . $behavioralData . "\n";
                }
                if (!empty($analysis['strategic_insights'])) {
                    $strategicInsights = is_array($analysis['strategic_insights']) 
                        ? json_encode($analysis['strategic_insights'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : $analysis['strategic_insights'];
                    $prompt .= "Insights Estratégicos (Preço, Concorrência, Tendências, Satisfação): " . $strategicInsights . "\n";
                }
                if (!empty($analysis['cost_optimization'])) {
                    $costOpt = is_array($analysis['cost_optimization']) 
                        ? json_encode($analysis['cost_optimization'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : $analysis['cost_optimization'];
                    $prompt .= "Otimização de Custos (Onde Cortar/Investir): " . $costOpt . "\n";
                }
            }
        }
        
        // Adiciona alertas de risco se disponíveis (do Agente Pedro)
        if (!empty($sentimentData['risk_alerts'])) {
            $prompt .= "\nALERTAS DE RISCO (Agente Pedro):\n";
            $riskAlerts = is_array($sentimentData['risk_alerts']) 
                ? json_encode($sentimentData['risk_alerts'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $sentimentData['risk_alerts'];
            $prompt .= $riskAlerts . "\n";
        }
        
        // Adiciona oportunidades de melhoria se disponíveis (do Agente Pedro)
        if (!empty($sentimentData['improvement_opportunities'])) {
            $prompt .= "\nOPORTUNIDADES DE MELHORIA (Agente Pedro):\n";
            $opportunities = is_array($sentimentData['improvement_opportunities']) 
                ? json_encode($sentimentData['improvement_opportunities'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $sentimentData['improvement_opportunities'];
            $prompt .= $opportunities . "\n";
        }
        
        $prompt .= "\nDIRETRIZES DE REDAÇÃO JORNALÍSTICA:\n\n";
        $prompt .= "1. TÍTULO:\n";
        $prompt .= "   - Crie um título impactante, informativo e preciso que capture a essência da matéria\n";
        $prompt .= "   - Evite sensacionalismo, mas seja atraente\n";
        $prompt .= "   - Inclua o símbolo da ação quando relevante\n";
        $prompt .= "   - Exemplo: \"PETR4 registra alta de 3,2% em dia de recuperação do setor petrolífero\"\n\n";
        
        $prompt .= "2. INTRODUÇÃO (Lead):\n";
        $prompt .= "   - Comece com um parágrafo forte que responda: O que está acontecendo? Por que é relevante?\n";
        $prompt .= "   - Contextualize a situação atual da ação no mercado\n";
        $prompt .= "   - Use dados concretos (preço, variação) para ancorar a narrativa\n";
        $prompt .= "   - Seja direto e objetivo, mas não superficial\n\n";
        
        $prompt .= "3. ANÁLISE FINANCEIRA APROFUNDADA:\n";
        $prompt .= "   - Não apenas liste números, mas explique o que eles significam\n";
        $prompt .= "   - Compare com médias históricas (52 semanas) quando relevante\n";
        $prompt .= "   - Contextualize indicadores como P/L e Dividend Yield no cenário atual\n";
        $prompt .= "   - Explique o significado do volume negociado e da capitalização de mercado\n";
        $prompt .= "   - Use linguagem técnica quando necessário, mas sempre explique termos complexos\n";
        $prompt .= "   - Transforme dados em insights: \"O P/L de X indica...\", \"A variação de Y% sugere...\"\n\n";
        
        $prompt .= "4. CONTEXTO DE MERCADO E SENTIMENTO:\n";
        $prompt .= "   - Analise profundamente como o sentimento do mercado está influenciando a ação\n";
        $prompt .= "   - Relacione as notícias recentes com os movimentos de preço\n";
        $prompt .= "   - Explique padrões: \"O sentimento positivo reflete...\", \"As notícias negativas indicam...\"\n";
        $prompt .= "   - Contextualize os tópicos em destaque e seu impacto\n";
        $prompt .= "   - Se houver análise de mercado ou macroeconômica, integre-a à narrativa de forma natural\n\n";
        
        $prompt .= "5. PERCEPÇÃO DE MARCA E COMPORTAMENTO (se disponível):\n";
        $prompt .= "   - Integre insights sobre percepção da marca de forma jornalística\n";
        $prompt .= "   - Explique como o engajamento e a confiança do investidor se refletem nos dados\n";
        $prompt .= "   - Use dados comportamentais para enriquecer a análise, não apenas listá-los\n";
        $prompt .= "   - Exemplo: \"A queda na confiança do investidor, medida em X pontos, coincide com...\"\n\n";
        
        $prompt .= "6. INSIGHTS ESTRATÉGICOS E PERSPECTIVAS:\n";
        $prompt .= "   - Transforme insights estratégicos em análise jornalística\n";
        $prompt .= "   - Discuta perspectivas de forma equilibrada, baseando-se nos dados apresentados\n";
        $prompt .= "   - Se houver alertas de risco, apresente-os de forma objetiva e contextualizada\n";
        $prompt .= "   - Evite especulação, mas não deixe de analisar tendências identificadas nos dados\n";
        $prompt .= "   - Seja cauteloso com projeções, sempre fundamentando em dados reais\n\n";
        
        $prompt .= "7. CONCLUSÃO:\n";
        $prompt .= "   - Encerre com uma síntese equilibrada que reúna os principais pontos\n";
        $prompt .= "   - Não faça recomendações explícitas de compra/venda\n";
        $prompt .= "   - Ofereça uma visão consolidada do cenário atual\n";
        $prompt .= "   - Deixe claro que investimentos requerem análise individual\n\n";
        
        $prompt .= "8. ESTILO E TOM JORNALÍSTICO:\n";
        $prompt .= "   - Use linguagem profissional, clara e objetiva\n";
        $prompt .= "   - Evite jargões desnecessários, mas use termos técnicos quando apropriado (sempre explicando)\n";
        $prompt .= "   - Mantenha tom neutro e informativo, sem sensacionalismo\n";
        $prompt .= "   - Seja preciso com números e dados\n";
        $prompt .= "   - Use parágrafos curtos e objetivos para facilitar a leitura\n";
        $prompt .= "   - Crie uma narrativa fluida que conecte os diferentes aspectos da análise\n\n";
        
        $prompt .= "9. PROFUNDIDADE E APROFUNDAMENTO:\n";
        $prompt .= "   - Não se limite a apresentar dados, aprofunde-se em seu significado\n";
        $prompt .= "   - Faça conexões entre diferentes indicadores e análises\n";
        $prompt .= "   - Explique o \"porquê\" por trás dos números, não apenas o \"o quê\"\n";
        $prompt .= "   - Use comparações e contextos históricos quando relevante\n";
        $prompt .= "   - Transforme análises técnicas em insights compreensíveis\n\n";
        
        $prompt .= "10. FORMATO:\n";
        $prompt .= "   - Use HTML para formatação profissional\n";
        $prompt .= "   - Utilize <h2> para subtítulos de seções\n";
        $prompt .= "   - Use <p> para parágrafos\n";
        $prompt .= "   - Use <strong> para destacar dados importantes\n";
        $prompt .= "   - Use <ul> e <li> para listas quando apropriado\n";
        $prompt .= "   - Mantenha formatação limpa e profissional\n\n";
        
        $prompt .= "IMPORTANTE - DISCLAIMER OBRIGATÓRIO:\n";
        $prompt .= "Ao final do conteúdo, SEMPRE inclua o seguinte aviso (não inclua no JSON, será adicionado automaticamente):\n";
        $prompt .= "\"*Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial e requer revisão humana antes da publicação. As informações apresentadas não constituem recomendação de investimento. Consulte sempre um analista financeiro certificado antes de tomar decisões de investimento.*\"\n\n";
        
        $prompt .= "FORMATO DE SAÍDA:\n";
        $prompt .= "Retorne APENAS um JSON válido com a seguinte estrutura:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Título da matéria\",\n";
        $prompt .= "  \"content\": \"Conteúdo completo em HTML formatado (sem o disclaimer, que será adicionado automaticamente)\"\n";
        $prompt .= "}\n\n";
        
        $prompt .= "EXEMPLO DE RESPOSTA ESPERADA (Response JSON):\n";
        $prompt .= "Siga este formato exato para a resposta:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"PETR4: Análise Aponta Sentimento Positivo do Mercado com Foco em Expansão e Sustentabilidade\",\n";
        $prompt .= "  \"content\": \"<h1>PETR4: Análise Aponta Sentimento Positivo do Mercado com Foco em Expansão e Sustentabilidade</h1>\\n\\n<p><strong>Por Agente Key - Redatora Veterana</strong><br>\\n<em>Publicado em: 03 de dezembro de 2025</em></p>\\n\\n<h2>Resumo Executivo</h2>\\n\\n<p>A <strong>Petrobras (PETR4)</strong> apresenta um cenário de <strong>sentimento positivo</strong> no mercado, com score de <strong>0.65</strong>, baseado na análise de <strong>15 notícias</strong> coletadas nas últimas 24 horas. A empresa demonstra forte crescimento em produção e investimentos estratégicos em energia renovável, gerando expectativas otimistas entre investidores.</p>\\n\\n<h2>Dados Financeiros</h2>\\n\\n<p>Com base nos dados coletados pelo <strong>Agente Júlia</strong>...</p>\\n\\n<h2>Análise de Sentimento do Mercado</h2>\\n\\n<p>O <strong>Agente Pedro</strong> identificou um sentimento predominantemente <strong>positivo</strong>...</p>\"\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        $prompt .= "NOTA: O campo 'content' deve conter HTML formatado completo, incluindo todas as seções da matéria. Use os dados fornecidos pelos agentes e complemente com informações da web quando necessário para enriquecer o contexto.\n\n";
        
        $prompt .= "INSTRUÇÕES IMPORTANTES:\n";
        $prompt .= "1. Use os dados fornecidos pelos Agentes Júlia e Pedro como base principal\n";
        $prompt .= "2. Se necessário, busque informações adicionais na web sobre a empresa para enriquecer o contexto\n";
        $prompt .= "3. Integre informações encontradas na web de forma natural na narrativa jornalística\n";
        $prompt .= "4. Sempre cite fontes quando usar informações externas\n";
        $prompt .= "5. Mantenha foco nos dados fornecidos pelos agentes, usando informações da web apenas para contexto\n";
        $prompt .= "6. Não inclua texto adicional antes ou depois do JSON\n";
        
        return $prompt;
    }

    /**
     * Parse da resposta de artigo gerado
     * 
     * @param string|array $content Conteúdo gerado (pode ser string ou array se já parseado)
     * @param string $symbol Símbolo da ação
     * @return array Artigo parseado
     */
    protected function parseArticleResponse($content, string $symbol): array
    {
        // Valida se content é string (pode ser array se response_format for 'json')
        if (!is_string($content)) {
            // Se já é array, verifica se tem estrutura esperada
            if (is_array($content) && isset($content['title']) && isset($content['content'])) {
                return [
                    'title' => trim((string)($content['title'] ?? '')),
                    'content' => trim((string)($content['content'] ?? '')),
                ];
            }
            // Se não é string nem array válido, converte para string
            $content = is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$content;
        }
        
        // Garante que $content é string para operações subsequentes
        $content = (string)$content;
        
        // Primeiro, tenta extrair JSON se presente (melhorado para capturar JSON completo)
        // Procura por JSON que contenha "title" e "content"
        if (preg_match('/\{[\s\S]*"title"[\s\S]*"content"[\s\S]*\}/', $content, $jsonMatches)) {
            $jsonStr = $jsonMatches[0];
            
            // Valida se JSON está completo (contagem básica de chaves)
            $openBraces = substr_count($jsonStr, '{');
            $closeBraces = substr_count($jsonStr, '}');
            
            // Se contagem de chaves está balanceada, tenta decodificar
            if ($openBraces === $closeBraces || abs($openBraces - $closeBraces) <= 1) {
                $jsonData = json_decode($jsonStr, true);
                if ($jsonData && isset($jsonData['title']) && isset($jsonData['content'])) {
                    return [
                        'title' => trim($jsonData['title']),
                        'content' => trim($jsonData['content']),
                    ];
                }
            }
        }
        
        // Remove markdown code blocks se presente
        $content = trim($content);
        if (preg_match('/```(?:html|json)?\s*\n?(.*?)\n?```/s', $content, $matches)) {
            $content = trim($matches[1]);
            // Tenta parsear como JSON se estiver em code block
            $jsonData = json_decode($content, true);
            if ($jsonData && isset($jsonData['title']) && isset($jsonData['content'])) {
                return [
                    'title' => trim($jsonData['title']),
                    'content' => trim($jsonData['content']),
                ];
            }
        }
        
        // Verifica se já é HTML
        $isHtml = preg_match('/<[a-z][\s\S]*>/i', $content);
        
        // Tenta extrair título e conteúdo
        $title = null;
        $articleContent = $content;
        
        if ($isHtml) {
            // Se é HTML, tenta extrair título de <h1>
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
                $title = strip_tags($matches[1]);
                $articleContent = preg_replace('/<h1[^>]*>.*?<\/h1>/i', '', $content, 1);
            } elseif (preg_match('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches)) {
                $title = strip_tags($matches[1]);
                $articleContent = preg_replace('/<h2[^>]*>.*?<\/h2>/i', '', $content, 1);
            }
        } else {
            // Se não é HTML, trata como Markdown
            $lines = explode("\n", trim($content));
            
            if (!empty($lines[0])) {
                $firstLine = trim($lines[0]);
                
                if (substr($firstLine, 0, 1) === '#') {
                    $title = trim($firstLine, '# ');
                    $articleContent = implode("\n", array_slice($lines, 1));
                } elseif (strlen($firstLine) < 100 && strpos($firstLine, '.') === false) {
                    $title = $firstLine;
                    $articleContent = implode("\n", array_slice($lines, 1));
                }
            }
        }
        
        // Se não encontrou título, gera um padrão
        if (empty($title)) {
            $title = "Análise {$symbol}: " . substr(strip_tags($content), 0, 50) . '...';
        }
        
        // Se não é HTML, converte Markdown para HTML
        if (!$isHtml) {
            $articleContent = $this->markdownToHtml($articleContent);
        }
        
        // Garante que há disclaimer em HTML
        $lowerContent = strtolower($articleContent);
        if (strpos($lowerContent, 'disclaimer') === false && 
            strpos($lowerContent, 'aviso') === false &&
            strpos($lowerContent, 'conteúdo foi gerado') === false) {
            $articleContent .= '<p><em>Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial e não constitui recomendação de investimento. Consulte sempre um profissional qualificado antes de tomar decisões financeiras.</em></p>';
        }
        
        return [
            'title' => trim($title),
            'content' => trim($articleContent), // Retorna HTML
        ];
    }
    
    /**
     * Converte Markdown básico para HTML
     */
    protected function markdownToHtml(string $markdown): string
    {
        $html = $markdown;
        
        // Títulos
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $html);
        
        // Negrito
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // Itálico
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Parágrafos (linhas vazias separam parágrafos)
        $lines = explode("\n", $html);
        $paragraphs = [];
        $currentParagraph = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if (!empty($currentParagraph)) {
                    $paragraphs[] = '<p>' . implode(' ', $currentParagraph) . '</p>';
                    $currentParagraph = [];
                }
            } elseif (preg_match('/^<h[1-6]>/', $line)) {
                // Se é um título, fecha parágrafo anterior e adiciona título
                if (!empty($currentParagraph)) {
                    $paragraphs[] = '<p>' . implode(' ', $currentParagraph) . '</p>';
                    $currentParagraph = [];
                }
                $paragraphs[] = $line;
            } else {
                $currentParagraph[] = $line;
            }
        }
        
        if (!empty($currentParagraph)) {
            $paragraphs[] = '<p>' . implode(' ', $currentParagraph) . '</p>';
        }
        
        $html = implode("\n", $paragraphs);
        
        // Quebras de linha dentro de parágrafos
        $html = preg_replace_callback('/<p>(.*?)<\/p>/s', function($matches) {
            $content = $matches[1];
            $content = preg_replace('/\n/', '<br>', $content);
            return '<p>' . $content . '</p>';
        }, $html);
        
        return $html;
    }
}

