<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\YahooFinanceService;
use App\Services\GeminiResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * Teste para verificar se o Gemini gera JSON corretamente para coleta de dados
 */
class GeminiJsonTest extends TestCase
{
    /**
     * Testa se o YahooFinanceService consegue obter JSON do Gemini
     */
    public function test_yahoo_finance_service_returns_json()
    {
        $service = new YahooFinanceService();
        
        // Testa com um símbolo conhecido
        $result = $service->getQuote('PETR4');
        
        $this->assertNotNull($result, 'Resultado não deve ser null');
        $this->assertIsArray($result, 'Resultado deve ser um array');
        
        // Verifica campos essenciais
        $this->assertArrayHasKey('symbol', $result, 'Deve ter campo symbol');
        $this->assertArrayHasKey('price', $result, 'Deve ter campo price');
        
        // Verifica se price é numérico ou null
        if ($result['price'] !== null) {
            $this->assertIsNumeric($result['price'], 'Price deve ser numérico');
        }
        
        // Log do resultado para debug
        Log::info('Test Gemini JSON - YahooFinanceService', [
            'result' => $result,
            'has_price' => isset($result['price']),
            'has_volume' => isset($result['volume']),
        ]);
    }

    /**
     * Testa se o GeminiResponseService consegue gerar JSON
     */
    public function test_gemini_response_service_generates_json()
    {
        $service = new GeminiResponseService();
        
        if (!$service->isConfigured()) {
            $this->markTestSkipped('Gemini API não está configurada');
        }
        
        // Testa geração de análise em JSON
        $testData = [
            'price' => 30.50,
            'change' => 0.50,
            'change_percent' => 1.67,
            'volume' => 50000000,
        ];
        
        $result = $service->generateAnalysis($testData, 'PETR4');
        
        $this->assertIsArray($result, 'Resultado deve ser um array');
        $this->assertArrayHasKey('resumo', $result, 'Deve ter campo resumo');
        
        Log::info('Test Gemini JSON - GeminiResponseService', [
            'result' => $result,
        ]);
    }

    /**
     * Testa se o prompt do YahooFinanceService está correto para gerar JSON
     */
    public function test_yahoo_finance_prompt_structure()
    {
        $service = new YahooFinanceService();
        
        // Usa reflection para acessar método protegido
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);
        
        $prompt = $method->invoke($service, 'PETR4');
        
        // Verifica se o prompt solicita JSON
        $this->assertStringContainsString('JSON', $prompt, 'Prompt deve solicitar JSON');
        $this->assertStringContainsString('price', $prompt, 'Prompt deve mencionar campo price');
        $this->assertStringContainsString('volume', $prompt, 'Prompt deve mencionar campo volume');
        
        Log::info('Test Gemini JSON - Prompt structure', [
            'prompt_length' => strlen($prompt),
            'has_json_request' => strpos($prompt, 'JSON') !== false,
        ]);
    }
}

