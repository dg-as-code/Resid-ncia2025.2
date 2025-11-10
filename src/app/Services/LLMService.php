<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Service para integração com LLM (Large Language Model)
 * 
 * Pode integrar com:
 * - Scripts Python locais
 * - APIs externas (OpenAI, Anthropic, etc.)
 * - Modelos locais via Python
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
        $this->provider = $config['provider'] ?? 'python';
        $this->pythonPath = $config['python_path'] ?? 'python3';
        $this->timeout = $config['timeout'] ?? 60;
        
        // Caminho do script Python
        $scriptPath = $config['python_script_path'] ?? 'llm/scripts/run_llm.py';
        $this->llmScriptPath = (substr($scriptPath, 0, 1) === '/') 
            ? $scriptPath 
            : base_path($scriptPath);
    }

    /**
     * Gera texto usando LLM baseado em dados consolidados
     * 
     * @param array $financialData Dados financeiros
     * @param array $sentimentData Análise de sentimento
     * @param string $symbol Símbolo da ação
     * @return array Matéria gerada (título e conteúdo)
     */
    public function generateArticle(array $financialData, array $sentimentData, string $symbol): array
    {
        try {
            // Prepara dados de entrada
            $inputData = $this->prepareInputData($financialData, $sentimentData, $symbol);

            // Tenta usar script Python se disponível
            if (file_exists($this->llmScriptPath)) {
                $result = $this->callPythonScript($inputData);
            } else {
                // Usa geração simples baseada em template
                $result = $this->generateSimpleArticle($financialData, $sentimentData, $symbol);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("LLM Service error for {$symbol}", [
                'error' => $e->getMessage()
            ]);

            // Fallback para geração simples
            return $this->generateSimpleArticle($financialData, $sentimentData, $symbol);
        }
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
            $result = Process::run([
                $this->pythonPath,
                $this->llmScriptPath,
                $inputData
            ]);

            if (!$result->successful()) {
                throw new \Exception($result->errorOutput());
            }

            $output = json_decode($result->output(), true);
            
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
     * Gera artigo simples baseado em template
     * 
     * @param array $financialData
     * @param array $sentimentData
     * @param string $symbol
     * @return array
     */
    protected function generateSimpleArticle(array $financialData, array $sentimentData, string $symbol): array
    {
        $price = $financialData['price'] ?? 'N/A';
        $change = $financialData['change'] ?? 0;
        $changePercent = $financialData['change_percent'] ?? 0;
        $volume = $financialData['volume'] ?? 'N/A';
        
        $sentiment = $sentimentData['sentiment'] ?? 'neutral';
        $newsCount = $sentimentData['news_count'] ?? 0;

        $trend = $change > 0 ? 'alta' : ($change < 0 ? 'queda' : 'estabilidade');
        $recommendation = $this->generateRecommendation($financialData, $sentimentData);

        $title = "Análise {$symbol}: Mercado em {$trend} - R\$ " . number_format($price, 2, ',', '.');

        $content = "## Análise de {$symbol}\n\n";
        $content .= "### Dados Financeiros\n\n";
        $content .= "A ação {$symbol} está sendo negociada a R\$ " . number_format($price, 2, ',', '.') . ".\n\n";
        
        if ($change != 0) {
            $changeFormatted = number_format(abs($change), 2, ',', '.');
            $percentFormatted = number_format(abs($changePercent), 2, ',', '.');
            $content .= "A variação do dia foi de R\$ {$changeFormatted} ({$percentFormatted}%), ";
            $content .= $change > 0 ? "representando uma valorização." : "representando uma desvalorização.";
            $content .= "\n\n";
        }

        if ($volume != 'N/A') {
            $content .= "O volume negociado foi de " . number_format($volume, 0, ',', '.') . " ações.\n\n";
        }

        $content .= "### Análise de Sentimento\n\n";
        $content .= "Com base na análise de {$newsCount} notícias, o sentimento do mercado é **{$sentiment}** ";
        $content .= "com score de " . number_format($sentimentData['sentiment_score'] ?? 0, 2) . ".\n\n";

        if (!empty($sentimentData['trending_topics'])) {
            $content .= "**Tópicos em destaque:** " . $sentimentData['trending_topics'] . "\n\n";
        }

        $content .= "### Recomendação\n\n";
        $content .= $recommendation . "\n\n";

        $content .= "*Este conteúdo foi gerado automaticamente com auxílio de IA e requer revisão humana antes da publicação.*";

        return [
            'title' => $title,
            'content' => $content,
        ];
    }

    /**
     * Gera recomendação baseada em dados
     * 
     * @param array $financialData
     * @param array $sentimentData
     * @return string
     */
    protected function generateRecommendation(array $financialData, array $sentimentData): string
    {
        $price = $financialData['price'] ?? 0;
        $changePercent = $financialData['change_percent'] ?? 0;
        $sentiment = $sentimentData['sentiment'] ?? 'neutral';
        $sentimentScore = $sentimentData['sentiment_score'] ?? 0;

        $recommendation = "Considerando os dados financeiros e a análise de sentimento do mercado, ";

        if ($sentiment == 'positive' && $changePercent > 0) {
            $recommendation .= "há sinais positivos, mas é importante avaliar cuidadosamente antes de investir. ";
            $recommendation .= "Recomenda-se análise técnica e fundamentalista adicional.";
        } elseif ($sentiment == 'negative' && $changePercent < 0) {
            $recommendation .= "há sinais de cautela. Recomenda-se aguardar mais informações ou evitar posições arriscadas. ";
            $recommendation .= "Consulte um analista financeiro antes de tomar decisões.";
        } else {
            $recommendation .= "o mercado mostra sinais mistos. Recomenda-se acompanhar de perto e buscar mais informações antes de investir.";
        }

        return $recommendation;
    }

    /**
     * Prepara dados de entrada para o LLM
     * 
     * @param array $financialData
     * @param array $sentimentData
     * @param string $symbol
     * @return string
     */
    protected function prepareInputData(array $financialData, array $sentimentData, string $symbol): string
    {
        $data = [
            'symbol' => $symbol,
            'financial' => $financialData,
            'sentiment' => $sentimentData,
        ];

        return json_encode($data);
    }
}

