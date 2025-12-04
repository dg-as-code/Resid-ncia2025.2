<?php

namespace Database\Factories;

use App\Models\StockSymbol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para criar ações monitoradas (Stock Symbols)
 * 
 * FLUXO DOS AGENTES:
 * StockSymbol é usado por todos os agentes no fluxo:
 * 
 * - Agente Júlia: Usa StockSymbol para identificar ações e coletar dados financeiros
 * - Agente Pedro: Usa StockSymbol para analisar sentimento de ações específicas
 * - Agente Key: Usa StockSymbol para gerar matérias sobre ações específicas
 * 
 * Campos importantes:
 * - symbol: Símbolo da ação (ex: PETR4, VALE3)
 * - company_name: Nome completo da empresa
 * - is_active: Se a ação está ativa no monitoramento
 * - is_default: Se é uma ação padrão do sistema
 */
class StockSymbolFactory extends Factory
{
    protected $model = StockSymbol::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Símbolos reais da B3 (Bolsa de Valores brasileira)
        $symbols = ['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3', 'WEGE3', 'MGLU3', 'RENT3', 'SUZB3', 'ELET3'];
        
        // Nomes das empresas correspondentes
        $companies = [
            'Petrobras S.A.',
            'Vale S.A.',
            'Itaú Unibanco Holding S.A.',
            'Banco Bradesco S.A.',
            'Ambev S.A.',
            'WEG S.A.',
            'Magazine Luiza S.A.',
            'Localiza Rent a Car S.A.',
            'Suzano S.A.',
            'Centrais Elétricas Brasileiras S.A. - Eletrobras'
        ];

        // Mapeia símbolo para empresa
        $symbolIndex = array_rand($symbols);
        $symbol = $symbols[$symbolIndex];
        $companyName = $companies[$symbolIndex];

        return [
            'symbol' => $symbol,
            'company_name' => $companyName,
            'is_active' => $this->faker->boolean(90), // 90% de chance de estar ativo
            'is_default' => $this->faker->boolean(50), // 50% de chance de ser padrão
        ];
    }

    /**
     * Estado: active - Ação ativa no monitoramento
     * 
     * Ações ativas são monitoradas pelos agentes (Júlia, Pedro, Key).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Estado: default - Ação padrão do sistema
     * 
     * Ações padrão são monitoradas automaticamente e aparecem em listas padrão.
     * Sempre são ativas.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'is_active' => true, // Ações padrão sempre estão ativas
        ]);
    }

    /**
     * Estado: inactive - Ação inativa no monitoramento
     * 
     * Ações inativas não são monitoradas pelos agentes.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_default' => false, // Ações inativas não podem ser padrão
        ]);
    }

    /**
     * Cria StockSymbol para um símbolo específico
     * 
     * @param string $symbol Símbolo da ação (ex: PETR4)
     * @param string|null $companyName Nome da empresa (opcional)
     * @return static
     */
    public function forSymbol(string $symbol, ?string $companyName = null): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => $symbol,
            'company_name' => $companyName ?? $attributes['company_name'] ?? $symbol,
        ]);
    }
}

