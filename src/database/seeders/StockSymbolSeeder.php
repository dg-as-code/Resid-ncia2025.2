<?php

namespace Database\Seeders;

use App\Models\StockSymbol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * StockSymbolSeeder - Popula ações monitoradas pelo sistema
 * 
 * Cria ações padrão para os agentes de IA coletarem dados.
 * Ações marcadas como 'is_default' são coletadas automaticamente.
 */
class StockSymbolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('🌱 Populando ações monitoradas...');

        $symbols = [
            // Ações principais (is_default = true) - coletadas automaticamente
            ['symbol' => 'PETR4', 'company_name' => 'Petrobras', 'is_active' => true, 'is_default' => true],
            ['symbol' => 'VALE3', 'company_name' => 'Vale', 'is_active' => true, 'is_default' => true],
            ['symbol' => 'ITUB4', 'company_name' => 'Itaú Unibanco', 'is_active' => true, 'is_default' => true],
            ['symbol' => 'BBDC4', 'company_name' => 'Bradesco', 'is_active' => true, 'is_default' => true],
            ['symbol' => 'ABEV3', 'company_name' => 'Ambev', 'is_active' => true, 'is_default' => true],
            
            // Ações adicionais (is_default = false) - coletadas sob demanda
            ['symbol' => 'WEGE3', 'company_name' => 'WEG', 'is_active' => true, 'is_default' => false],
            ['symbol' => 'MGLU3', 'company_name' => 'Magazine Luiza', 'is_active' => true, 'is_default' => false],
            ['symbol' => 'RENT3', 'company_name' => 'Localiza', 'is_active' => true, 'is_default' => false],
            ['symbol' => 'SUZB3', 'company_name' => 'Suzano', 'is_active' => true, 'is_default' => false],
            ['symbol' => 'ELET3', 'company_name' => 'Centrais Elétricas Brasileiras', 'is_active' => true, 'is_default' => false],
        ];

        $created = 0;
        $updated = 0;

        foreach ($symbols as $symbolData) {
            $symbol = StockSymbol::updateOrCreate(
                ['symbol' => $symbolData['symbol']],
                $symbolData
            );

            if ($symbol->wasRecentlyCreated) {
                $created++;
                $this->command->line("  ✓ Criado: {$symbol->symbol} - {$symbol->company_name}");
            } else {
                $updated++;
                $this->command->line("  ↻ Atualizado: {$symbol->symbol} - {$symbol->company_name}");
            }
        }

        $this->command->info("✅ {$created} ação(ões) criada(s), {$updated} atualizada(s)");
        
        // Estatísticas
        $totalActive = StockSymbol::where('is_active', true)->count();
        $totalDefault = StockSymbol::where('is_default', true)->where('is_active', true)->count();
        
        $this->command->info("📊 Total: {$totalActive} ação(ões) ativa(s), {$totalDefault} ação(ões) padrão");
        
        Log::info('StockSymbolSeeder: Ações populadas', [
            'created' => $created,
            'updated' => $updated,
            'total_active' => $totalActive,
            'total_default' => $totalDefault,
        ]);
    }
}

