<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Services\GeminiResponseService;

/**
 * Service para integração com LLM (Large Language Model)
 * 
 * Usado pelo Agente Key (Redatora Veterana de Jornal Financeiro) para gerar
 * matérias jornalísticas profissionais baseadas nos dados coletados pelos
 * Agentes Júlia (dados financeiros) e Pedro (análise de sentimento).
 * 
 * Fluxo:
 * 1. Recebe dados financeiros (Agente Júlia) e análise de sentimento (Agente Pedro)
 * 2. Prepara dados para o LLM (Gemini)
 * 3. Gera artigo jornalístico profissional via GeminiResponseService
 * 4. Fallback para geração simples se Gemini falhar
 * 
 * Pode integrar com:
 * - Google Gemini API (via GeminiResponseService) - PRIORITÁRIO
 * - Scripts Python locais (fallback)
 * - APIs externas (OpenAI, Anthropic, etc.) - futuro
 */
class LLMService
{
    protected $pythonPath;
    protected $llmScriptPath;
    protected $provider;
    protected $timeout;
    protected $config;

    public function __construct()
    {
        $config = config('services.llm');
        $this->config = $config;
        $this->provider = $config['provider'] ?? 'gemini';
        $this->pythonPath = $config['python_path'] ?? 'python3';
        $this->timeout = $config['timeout'] ?? 60;
        
        // Caminho do script Python
        $scriptPath = $config['python_script_path'] ?? 'llm/scripts/run_llm.py';
        $this->llmScriptPath = (substr($scriptPath, 0, 1) === '/') 
            ? $scriptPath 
            : base_path($scriptPath);
    }

    /**
     * Gera matéria jornalística usando LLM baseado em dados consolidados
     * 
     * Agente Key (Redatora Veterana): Transforma dados técnicos em redação jornalística
     * profissional, clara, objetiva e aprofundada.
     * 
     * Fluxo:
     * 1. Prioriza GeminiResponseService (via API direta) se configurado
     * 2. Valida resultado do Gemini (deve ter title e content)
     * 3. Fallback para geração simples se Gemini falhar ou não estiver configurado
     * 
     * @param array $financialData Dados financeiros do Agente Júlia
     * @param array $sentimentData Análise completa de sentimento do Agente Pedro
     * @param string $symbol Símbolo da ação
     * @return array Matéria gerada com 'title' e 'content' (HTML formatado)
     */
    public function generateArticle(array $financialData, array $sentimentData, string $symbol): array
    {
        try {
            // Verifica se pode usar Gemini diretamente (prioritário)
            if ($this->provider === 'gemini' && $this->canUseGeminiDirectly()) {
                try {
                    // Usa GeminiResponseService para gerar artigo profissional
                    // Passa dados diretamente (sem JSON intermediário)
                    $geminiService = new GeminiResponseService();
                    
                    if (!$geminiService->isConfigured()) {
                        throw new \Exception("Gemini API não está configurada");
                    }
                    
                    $result = $geminiService->generateArticle($financialData, $sentimentData, $symbol);
                    
                    // Valida se o resultado tem título e conteúdo
                    if (empty($result['title']) || empty($result['content'])) {
                        throw new \Exception("Resultado do Gemini incompleto: título ou conteúdo vazio");
                    }
                    
                    Log::info("LLMService: Artigo gerado com sucesso via Gemini", [
                        'symbol' => $symbol,
                        'title_length' => strlen($result['title']),
                        'content_length' => strlen($result['content']),
                    ]);
                    
                    return $result;
                } catch (\Exception $e) {
                    Log::warning("LLMService: Gemini API falhou, usando fallback", [
                        'error' => $e->getMessage(),
                        'symbol' => $symbol,
                        'trace' => substr($e->getTraceAsString(), 0, 300),
                    ]);
                    // Continua para fallback
                }
            }
            
            // Fallback: Geração simples baseada em template
            // Usado quando Gemini não está disponível ou falhou
            Log::info("LLMService: Usando geração simples (fallback)", [
                'symbol' => $symbol,
                'provider' => $this->provider,
                'gemini_available' => $this->canUseGeminiDirectly(),
            ]);
            
            return $this->generateSimpleArticle($financialData, $sentimentData, $symbol);

        } catch (\Exception $e) {
            Log::error("LLMService: Erro crítico na geração de artigo", [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // Fallback final para geração simples
            return $this->generateSimpleArticle($financialData, $sentimentData, $symbol);
        }
    }

    /**
     * Verifica se pode usar Gemini diretamente
     */
    protected function canUseGeminiDirectly(): bool
    {
        $geminiConfig = config('services.llm.gemini', []);
        $apiKey = $geminiConfig['api_key'] ?? env('GEMINI_API_KEY');
        return !empty($apiKey);
    }

    /**
     * Chama Gemini API diretamente para gerar artigo
     * 
     * DEPRECADO: Este método não é mais usado diretamente.
     * A chamada ao Gemini agora é feita diretamente em generateArticle().
     * Mantido para compatibilidade com código legado.
     * 
     * @deprecated Use GeminiResponseService diretamente em generateArticle()
     */
    protected function callGeminiAPI(string $inputData, string $symbol): array
    {
        try {
            $inputArray = json_decode($inputData, true);
            $financialData = $inputArray['financial'] ?? [];
            $sentimentData = $inputArray['sentiment'] ?? [];
            
            // Usa o serviço de resposta Gemini
            $geminiService = new GeminiResponseService();
            
            if (!$geminiService->isConfigured()) {
                throw new \Exception("Gemini API não está configurada");
            }
            
            $result = $geminiService->generateArticle($financialData, $sentimentData, $symbol);
            
            return $result;

        } catch (\Exception $e) {
            Log::warning("LLMService: callGeminiAPI error", [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
            ]);
            throw $e;
        }
    }

    /**
     * Constrói prompt para geração de artigo
     * 
     * DEPRECADO: Este método não é mais usado diretamente.
     * O prompt é construído pelo GeminiResponseService::buildArticlePrompt().
     * Mantido para compatibilidade com código legado ou scripts Python.
     * 
     * @deprecated Use GeminiResponseService::buildArticlePrompt() diretamente
     */
    protected function buildArticlePrompt(array $inputArray, string $symbol): string
    {
        $financial = $inputArray['financial'] ?? [];
        $sentiment = $inputArray['sentiment'] ?? [];
        $companyName = $financial['company_name'] ?? $symbol;

        $prompt = "Você é um jornalista financeiro veterano com mais de 15 anos de experiência. ";
        $prompt .= "Crie uma matéria jornalística profissional sobre a ação {$symbol} ({$companyName}) baseada nos seguintes dados:\n\n";
        
        $prompt .= "DADOS FINANCEIROS (Agente Júlia):\n";
        $prompt .= "- Preço atual: R$ " . ($financial['price'] ?? 'N/A') . "\n";
        $prompt .= "- Fechamento anterior: R$ " . ($financial['previous_close'] ?? 'N/A') . "\n";
        $prompt .= "- Variação: " . ($financial['change'] ?? 0) . " (" . ($financial['change_percent'] ?? 0) . "%)\n";
        $prompt .= "- Volume: " . ($financial['volume'] ?? 'N/A') . "\n";
        $prompt .= "- Capitalização: R$ " . ($financial['market_cap'] ?? 'N/A') . "\n";
        $prompt .= "- P/L: " . ($financial['pe_ratio'] ?? 'N/A') . "\n";
        $prompt .= "- Dividend Yield: " . ($financial['dividend_yield'] ?? 'N/A') . "%\n";
        
        $prompt .= "\nANÁLISE DE SENTIMENTO (Agente Pedro):\n";
        $prompt .= "- Sentimento: " . ($sentiment['sentiment'] ?? 'neutral') . "\n";
        $prompt .= "- Score: " . ($sentiment['sentiment_score'] ?? 0) . "\n";
        $prompt .= "- Notícias analisadas: " . ($sentiment['news_count'] ?? 0) . "\n";
        $prompt .= "- Positivas: " . ($sentiment['positive_count'] ?? 0) . "\n";
        $prompt .= "- Negativas: " . ($sentiment['negative_count'] ?? 0) . "\n";
        $prompt .= "- Neutras: " . ($sentiment['neutral_count'] ?? 0) . "\n";
        
        if (!empty($sentiment['trending_topics'])) {
            $topics = is_array($sentiment['trending_topics']) 
                ? implode(', ', $sentiment['trending_topics']) 
                : $sentiment['trending_topics'];
            $prompt .= "- Tópicos em destaque: " . $topics . "\n";
        }
        
        $prompt .= "\nCrie uma matéria jornalística profissional, clara, objetiva e aprofundada com:\n";
        $prompt .= "1. Um título atraente, informativo e preciso\n";
        $prompt .= "2. Análise aprofundada dos dados financeiros (não apenas listar números)\n";
        $prompt .= "3. Contexto sobre o sentimento do mercado e sua relação com os dados financeiros\n";
        $prompt .= "4. Uma conclusão equilibrada e responsável\n";
        $prompt .= "\nFormato: Retorne um JSON com 'title' e 'content' (em HTML formatado).";

        return $prompt;
    }

    /**
     * Parse resposta do Gemini
     * 
     * DEPRECADO: Este método não é mais usado diretamente.
     * O parsing é feito pelo GeminiResponseService::parseArticleResponse().
     * Mantido para compatibilidade com código legado.
     * 
     * @deprecated Use GeminiResponseService::parseArticleResponse() diretamente
     */
    protected function parseGeminiResponse(string $content, array $inputArray, string $symbol): array
    {
        // Tenta extrair JSON da resposta
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['title']) && isset($json['content'])) {
                return $json;
            }
        }

        // Se não conseguir extrair JSON, usa o conteúdo completo como artigo
        $companyName = $inputArray['financial']['company_name'] ?? $symbol;
        $price = $inputArray['financial']['price'] ?? null;
        
        $title = "Análise {$symbol} ({$companyName})";
        if ($price !== null && is_numeric($price)) {
            $title .= ": R\$ " . number_format($price, 2, ',', '.');
        }
        
        return [
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Chama script Python para gerar texto
     * 
     * @param string $inputData
     * @return array
     */
    protected function callPythonScript(string $inputData): array
    {
        try {
            $process = new Process([
                $this->pythonPath,
                $this->llmScriptPath,
                $inputData
            ]);
            
            $process->setTimeout($this->timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                throw new \Exception($errorOutput);
            }

            $output = json_decode($process->getOutput(), true);
            
            return [
                'title' => $output['title'] ?? 'Análise Financeira',
                'content' => $output['content'] ?? $output['text'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::warning("Python script error, using fallback", [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Gera artigo simples baseado em template (Fallback)
     * 
     * Usado quando Gemini não está disponível ou falha.
     * Gera um artigo básico mas completo usando template estruturado.
     * 
     * @param array $financialData Dados financeiros do Agente Júlia
     * @param array $sentimentData Análise de sentimento do Agente Pedro
     * @param string $symbol Símbolo da ação
     * @return array Artigo gerado com 'title' e 'content' (Markdown)
     */
    protected function generateSimpleArticle(array $financialData, array $sentimentData, string $symbol): array
    {
        $companyName = $financialData['company_name'] ?? $symbol;
        $price = $financialData['price'] ?? 'N/A';
        $change = $financialData['change'] ?? 0;
        $changePercent = $financialData['change_percent'] ?? 0;
        $volume = $financialData['volume'] ?? 'N/A';
        $marketCap = $financialData['market_cap'] ?? null;
        $peRatio = $financialData['pe_ratio'] ?? null;
        $dividendYield = $financialData['dividend_yield'] ?? null;
        $high52w = $financialData['high_52w'] ?? null;
        $low52w = $financialData['low_52w'] ?? null;
        
        $sentiment = $sentimentData['sentiment'] ?? 'neutral';
        $sentimentScore = $sentimentData['sentiment_score'] ?? 0;
        $newsCount = $sentimentData['news_count'] ?? 0;
        $positiveCount = $sentimentData['positive_count'] ?? 0;
        $negativeCount = $sentimentData['negative_count'] ?? 0;
        $neutralCount = $sentimentData['neutral_count'] ?? 0;
        $trendingTopics = $sentimentData['trending_topics'] ?? null;

        $trend = $change > 0 ? 'alta' : ($change < 0 ? 'queda' : 'estabilidade');
        $recommendation = $this->generateRecommendation($financialData, $sentimentData);

        // Título informativo
        $title = "Análise {$symbol} ({$companyName}): Mercado em {$trend}";
        if ($price !== 'N/A' && is_numeric($price)) {
            $title .= " - R\$ " . number_format($price, 2, ',', '.');
        }

        // Conteúdo estruturado
        $content = "<h2>Análise de {$symbol} ({$companyName})</h2>\n\n";
        
        // Seção: Dados Financeiros
        $content .= "<h3>Dados Financeiros</h3>\n\n";
        $content .= "<p>A ação <strong>{$symbol}</strong> ({$companyName}) está sendo negociada a ";
        if ($price !== 'N/A' && is_numeric($price)) {
            $content .= "<strong>R\$ " . number_format($price, 2, ',', '.') . "</strong>.";
        } else {
            $content .= "valor não disponível.";
        }
        $content .= "</p>\n\n";
        
        if ($change != 0) {
            $changeFormatted = number_format(abs($change), 2, ',', '.');
            $percentFormatted = number_format(abs($changePercent), 2, ',', '.');
            $direction = $change > 0 ? "valorização" : "desvalorização";
            $content .= "<p>A variação do dia foi de <strong>R\$ {$changeFormatted}</strong> ";
            $content .= "({$percentFormatted}%), representando uma <strong>{$direction}</strong>.</p>\n\n";
        }

        if ($volume !== 'N/A' && is_numeric($volume)) {
            $content .= "<p>O volume negociado foi de <strong>" . number_format($volume, 0, ',', '.') . " ações</strong>.</p>\n\n";
        }

        // Indicadores adicionais se disponíveis
        if ($marketCap !== null || $peRatio !== null || $dividendYield !== null) {
            $content .= "<h4>Indicadores Financeiros</h4>\n<ul>\n";
            if ($marketCap !== null) {
                $content .= "<li><strong>Capitalização de Mercado:</strong> R\$ " . number_format($marketCap, 0, ',', '.') . "</li>\n";
            }
            if ($peRatio !== null) {
                $content .= "<li><strong>P/L:</strong> " . number_format($peRatio, 2, ',', '.') . "</li>\n";
            }
            if ($dividendYield !== null) {
                $content .= "<li><strong>Dividend Yield:</strong> " . number_format($dividendYield, 2, ',', '.') . "%</li>\n";
            }
            if ($high52w !== null && $low52w !== null) {
                $content .= "<li><strong>Faixa 52 semanas:</strong> R\$ " . number_format($low52w, 2, ',', '.') . " - R\$ " . number_format($high52w, 2, ',', '.') . "</li>\n";
            }
            $content .= "</ul>\n\n";
        }

        // Seção: Análise de Sentimento
        $content .= "<h3>Análise de Sentimento do Mercado</h3>\n\n";
        $content .= "<p>Com base na análise de <strong>{$newsCount} notícias</strong>, o sentimento do mercado é ";
        $sentimentText = $sentiment === 'positive' ? 'positivo' : ($sentiment === 'negative' ? 'negativo' : 'neutro');
        $content .= "<strong>{$sentimentText}</strong> com score de <strong>" . number_format($sentimentScore, 2, ',', '.') . "</strong>.</p>\n\n";

        if ($positiveCount > 0 || $negativeCount > 0 || $neutralCount > 0) {
            $content .= "<p><strong>Distribuição:</strong> ";
            $parts = [];
            if ($positiveCount > 0) $parts[] = "{$positiveCount} positivas";
            if ($negativeCount > 0) $parts[] = "{$negativeCount} negativas";
            if ($neutralCount > 0) $parts[] = "{$neutralCount} neutras";
            $content .= implode(', ', $parts) . ".</p>\n\n";
        }

        if (!empty($trendingTopics)) {
            $topicsText = is_array($trendingTopics) ? implode(', ', $trendingTopics) : $trendingTopics;
            $content .= "<p><strong>Tópicos em destaque:</strong> {$topicsText}</p>\n\n";
        }

        // Seção: Recomendação
        $content .= "<h3>Recomendação</h3>\n\n";
        $content .= "<p>{$recommendation}</p>\n\n";

        // Disclaimer automático (obrigatório)
        $content .= "<hr>\n\n";
        $content .= "<p><em>Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial e requer revisão humana antes da publicação. As informações apresentadas não constituem recomendação de investimento. Consulte sempre um analista financeiro certificado antes de tomar decisões de investimento.</em></p>";

        return [
            'title' => $title,
            'content' => $content, // Markdown/HTML simples
        ];
    }

    /**
     * Gera recomendação baseada em dados financeiros e análise de sentimento
     * 
     * Analisa os dados coletados pelos Agentes Júlia e Pedro para gerar
     * uma recomendação equilibrada e responsável.
     * 
     * @param array $financialData Dados financeiros do Agente Júlia
     * @param array $sentimentData Análise de sentimento do Agente Pedro
     * @return string Recomendação textual
     */
    protected function generateRecommendation(array $financialData, array $sentimentData): string
    {
        $price = $financialData['price'] ?? 0;
        $changePercent = $financialData['change_percent'] ?? 0;
        $sentiment = $sentimentData['sentiment'] ?? 'neutral';
        $sentimentScore = $sentimentData['sentiment_score'] ?? 0;
        
        // Usa insights estratégicos se disponíveis
        $strategicAnalysis = $sentimentData['strategic_analysis'] ?? null;
        $actionableInsights = $sentimentData['actionable_insights'] ?? null;
        $riskAlerts = $sentimentData['risk_alerts'] ?? null;

        $recommendation = "Considerando os dados financeiros coletados pelo Agente Júlia e a análise de sentimento do mercado realizada pelo Agente Pedro, ";

        // Análise combinada de sentimento e variação
        if ($sentiment == 'positive' && $changePercent > 0) {
            $recommendation .= "há sinais positivos tanto no desempenho financeiro quanto no sentimento do mercado. ";
            $recommendation .= "No entanto, é fundamental realizar uma análise técnica e fundamentalista adicional antes de tomar decisões de investimento. ";
        } elseif ($sentiment == 'negative' && $changePercent < 0) {
            $recommendation .= "há sinais de cautela tanto nos indicadores financeiros quanto no sentimento do mercado. ";
            $recommendation .= "Recomenda-se aguardar mais informações ou evitar posições arriscadas no momento. ";
            $recommendation .= "Consulte um analista financeiro certificado antes de tomar decisões.";
        } elseif ($sentiment == 'positive' && $changePercent < 0) {
            $recommendation .= "o sentimento do mercado é positivo, mas os indicadores financeiros mostram queda. ";
            $recommendation .= "Esta divergência sugere cautela e requer análise mais aprofundada antes de investir.";
        } elseif ($sentiment == 'negative' && $changePercent > 0) {
            $recommendation .= "os indicadores financeiros mostram valorização, mas o sentimento do mercado é negativo. ";
            $recommendation .= "Esta situação requer atenção especial e análise detalhada dos fatores que influenciam o sentimento.";
        } else {
            $recommendation .= "o mercado mostra sinais mistos. Recomenda-se acompanhar de perto os desenvolvimentos e buscar mais informações antes de investir.";
        }

        // Adiciona insights estratégicos se disponíveis
        if (!empty($actionableInsights) && is_array($actionableInsights)) {
            $recommendation .= " Destacam-se os seguintes insights estratégicos: ";
            $insightsText = [];
            foreach (array_slice($actionableInsights, 0, 3) as $insight) {
                if (is_array($insight) && isset($insight['insight'])) {
                    $insightsText[] = $insight['insight'];
                } elseif (is_string($insight)) {
                    $insightsText[] = $insight;
                }
            }
            if (!empty($insightsText)) {
                $recommendation .= implode('; ', $insightsText) . ".";
            }
        }

        // Adiciona alertas de risco se disponíveis
        if (!empty($riskAlerts) && is_array($riskAlerts)) {
            $highRiskAlerts = array_filter($riskAlerts, function($alert) {
                if (is_array($alert) && isset($alert['severity'])) {
                    $severity = strtolower($alert['severity']);
                    return in_array($severity, ['crítica', 'critical', 'alta', 'high']);
                }
                return false;
            });
            
            if (!empty($highRiskAlerts)) {
                $recommendation .= " Atenção: foram identificados alertas de risco que merecem consideração especial.";
            }
        }

        return $recommendation;
    }

    /**
     * Prepara dados de entrada para o LLM
     * 
     * Converte arrays de dados financeiros e de sentimento em JSON estruturado.
     * Usado principalmente para scripts Python (fallback).
     * 
     * Nota: O GeminiResponseService recebe os dados diretamente como arrays,
     * não como JSON string. Este método é mantido para compatibilidade.
     * 
     * @param array $financialData Dados financeiros do Agente Júlia (completos)
     * @param array $sentimentData Análise de sentimento do Agente Pedro (completos)
     * @param string $symbol Símbolo da ação
     * @return string JSON string com dados estruturados
     */
    protected function prepareInputData(array $financialData, array $sentimentData, string $symbol): string
    {
        // Estrutura completa dos dados para o LLM
        $data = [
            'symbol' => $symbol,
            'company_name' => $financialData['company_name'] ?? $symbol,
            'financial' => $financialData, // Todos os campos do Agente Júlia
            'sentiment' => $sentimentData, // Todos os campos do Agente Pedro
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

