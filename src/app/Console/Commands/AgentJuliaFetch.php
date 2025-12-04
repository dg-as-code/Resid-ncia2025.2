<?php

namespace App\Console\Commands;

use App\Models\StockSymbol;
use App\Models\FinancialData;
use App\Services\YahooFinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Agente Júlia: Coleta dados financeiros atualizados
 * 
 * Responsabilidade: Coletar dados financeiros de mercado usando GEMINI API
 * 
 * Este comando deve ser executado frequentemente para manter os dados atualizados.
 * Usa GEMINI para buscar e analisar informações financeiras sobre ações.
 * 
 * Nota: GEMINI pode não ter dados atualizados em tempo real. Para produção,
 * considere usar APIs financeiras especializadas ou combinar com outras fontes.
 */
class AgentJuliaFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:julia:fetch 
                            {--company_name= : Nome da empresa a ser analisada (opcional)}
                            {--all : Coletar dados de todas as ações monitoradas}
                            {--use-python : Usar script Python AgentJulia.py diretamente}
                            {--python-path=python3 : Caminho para o executável Python}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agente Júlia: Coleta dados financeiros atualizados de mercado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(' Agente Júlia iniciando coleta de dados financeiros...');

        try {
            $company_name = $this->option('company_name');
            $all = $this->option('all');
            $usePython = $this->option('use-python');
            $pythonPath = $this->option('python-path');
            
            $collectedCount = 0;
            $errorCount = 0;

            // Se usar Python, chama script diretamente
            if ($usePython && $company_name) {
                return $this->handlePythonExecution($company_name, $pythonPath);
            }

            // Modo padrão: usa YahooFinanceService ou busca símbolos
            $service = new YahooFinanceService();

            // Determina quais símbolos coletar
            $symbolsToCollect = $this->getSymbolsToCollect($company_name, $all);

            if (empty($symbolsToCollect)) {
                // Se não há símbolos mas há company_name, tenta usar Python
                if ($company_name) {
                    $this->info(" Nenhum símbolo encontrado, tentando usar script Python...");
                    return $this->handlePythonExecution($company_name, $pythonPath);
                }
                
                $this->warn(' Nenhuma ação encontrada para coleta.');
                return Command::SUCCESS;
            }

            $this->info(" Coletando dados de " . count($symbolsToCollect) . " ação(ões)...");

            $bar = $this->output->createProgressBar(count($symbolsToCollect));
            $bar->start();

            foreach ($symbolsToCollect as $stockSymbol) {
                try {
                    $quote = $service->getQuote($stockSymbol->symbol);

                    if ($quote) {
                        // getQuote() já retorna dados normalizados, salva diretamente
                        $symbol = $quote['symbol'] ?? $stockSymbol->symbol;
                        
                        // Atualiza StockSymbol se necessário
                        if ($quote['company_name'] && $stockSymbol->company_name !== $quote['company_name']) {
                            $stockSymbol->update(['company_name' => $quote['company_name']]);
                        }
                        
                        $financial = FinancialData::create([
                            'stock_symbol_id' => $stockSymbol->id,
                            'symbol' => $symbol,
                            'price' => $quote['price'] ?? null,
                            'previous_close' => $quote['previous_close'] ?? null,
                            'change' => $quote['change'] ?? null,
                            'change_percent' => $quote['change_percent'] ?? null,
                            'volume' => $quote['volume'] ?? null,
                            'market_cap' => $quote['market_cap'] ?? null,
                            'pe_ratio' => $quote['pe_ratio'] ?? null,
                            'dividend_yield' => $quote['dividend_yield'] ?? null,
                            'high_52w' => $quote['high_52w'] ?? null,
                            'low_52w' => $quote['low_52w'] ?? null,
                            'raw_data' => $quote['raw_data'] ?? null,
                            'collected_at' => $quote['collected_at'] ?? now(),
                        ]);
                        
                        if ($financial) {
                            $collectedCount++;
                            Log::info('Agent Julia: Dados coletados', [
                                'symbol' => $symbol,
                                'price' => $quote['price'] ?? null,
                            ]);
                        } else {
                            $errorCount++;
                        }
                    } else {
                        $errorCount++;
                        Log::warning('Agent Julia: Falha ao coletar dados', [
                            'symbol' => $stockSymbol->symbol,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Agent Julia: Erro ao coletar símbolo', [
                        'symbol' => $stockSymbol->symbol,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
                
                // Pequeno delay para não sobrecarregar a API
                usleep(500000); // 0.5 segundos
            }

            $bar->finish();
            $this->newLine();

            $this->info("Coleta concluída! {$collectedCount} ação(ões) coletada(s) com sucesso.");
            if ($errorCount > 0) {
                $this->warn(" {$errorCount} erro(s) durante a coleta.");
            }

            Log::info('Agent Julia: Coleta de dados financeiros concluída', [
                'collected' => $collectedCount,
                'errors' => $errorCount,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error(' Erro ao coletar dados: ' . $e->getMessage());
            Log::error('Agent Julia: Erro ao coletar dados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Executa script Python AgentJulia.py e processa JSON retornado
     * 
     * @param string $companyName
     * @param string $pythonPath
     * @return int
     */
    protected function handlePythonExecution(string $companyName, string $pythonPath): int
    {
        try {
            $this->info(" Executando script Python AgentJulia.py para: {$companyName}");
            
            $scriptPath = base_path('llm/models/AgentJulia.py');
            
            if (!file_exists($scriptPath)) {
                $this->error(" Script Python não encontrado em: {$scriptPath}");
                return Command::FAILURE;
            }

            // Executa script Python
            $process = new Process([
                $pythonPath,
                $scriptPath,
                $companyName
            ]);
            
            $process->setTimeout(300); // 5 minutos de timeout
            $process->run();

            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                $this->error(" Erro ao executar script Python: " . $errorOutput);
                Log::error('Agent Julia: Erro ao executar script Python', [
                    'error' => $errorOutput,
                    'company_name' => $companyName,
                    'exit_code' => $process->getExitCode(),
                ]);
                return Command::FAILURE;
            }

            $jsonOutput = $process->getOutput();
            
            if (empty($jsonOutput)) {
                $this->warn(" Script Python não retornou dados.");
                return Command::FAILURE;
            }

            // Decodifica JSON
            $data = json_decode($jsonOutput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error(" Erro ao decodificar JSON: " . json_last_error_msg());
                Log::error('Agent Julia: Erro ao decodificar JSON do Python', [
                    'error' => json_last_error_msg(),
                    'output' => substr($jsonOutput, 0, 500),
                ]);
                return Command::FAILURE;
            }

            // Processa e salva dados
            $symbol = $data['symbol'] ?? $companyName;
            $companyNameFromData = $data['company_name'] ?? $companyName;
            
            // Busca ou cria StockSymbol
            $stockSymbol = StockSymbol::where('symbol', $symbol)
                ->orWhere('company_name', 'like', "%{$companyName}%")
                ->first();
            
            if (!$stockSymbol) {
                $stockSymbol = StockSymbol::create([
                    'symbol' => $symbol,
                    'company_name' => $companyNameFromData,
                    'is_active' => true,
                ]);
            }
            
            // Extrai valores numéricos
            $extractNumeric = function($value) {
                if ($value === null) return null;
                if (is_numeric($value)) return (float) $value;
                if (is_string($value)) {
                    $cleaned = preg_replace('/[^0-9.,\-]/', '', $value);
                    $cleaned = str_replace(',', '.', $cleaned);
                    return is_numeric($cleaned) ? (float) $cleaned : null;
                }
                return null;
            };
            
            // Cria FinancialData
            $financial = FinancialData::create([
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $symbol,
                'price' => $extractNumeric($data['price'] ?? null),
                'previous_close' => $extractNumeric($data['previous_close'] ?? null),
                'change' => $extractNumeric($data['change'] ?? null),
                'change_percent' => $extractNumeric($data['change_percent'] ?? null),
                'volume' => $extractNumeric($data['volume'] ?? null),
                'market_cap' => $extractNumeric($data['market_cap'] ?? null),
                'pe_ratio' => $extractNumeric($data['pe_ratio'] ?? null),
                'dividend_yield' => $extractNumeric($data['dividend_yield'] ?? null),
                'high_52w' => $extractNumeric($data['high_52w'] ?? null),
                'low_52w' => $extractNumeric($data['low_52w'] ?? null),
                'raw_data' => $data,
                'collected_at' => now(),
            ]);

            if ($financial) {
                $this->info(" ✓ Dados coletados e salvos com sucesso!");
                $this->info("   Symbol: {$financial->symbol}");
                $this->info("   Preço: R$ " . number_format($financial->price ?? 0, 2, ',', '.'));
                
                return Command::SUCCESS;
            } else {
                $this->error(" Erro ao salvar dados no banco.");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error(" Erro ao executar script Python: " . $e->getMessage());
            Log::error('Agent Julia: Erro ao executar script Python', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_name' => $companyName,
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Obtém símbolos para coleta baseado nas opções
     * 
     * @param string|null $company_name
     * @param bool $all
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getSymbolsToCollect(?string $company_name, bool $all)
    {
        if ($all) {
            return StockSymbol::active()->get();
        } elseif ($company_name) {
            return StockSymbol::where('company_name', $company_name)->where('is_active', true)->get();
        } else {
            return StockSymbol::active()->default()->get();
        }
    }
}

