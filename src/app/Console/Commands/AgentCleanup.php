<?php

namespace App\Console\Commands;

use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Agente Cleanup: Limpeza e manutenção
 * 
 * Responsabilidade: Limpar arquivos temporários, caches antigos e manter
 * o sistema organizado
 * 
 * Este comando executa rotinas de limpeza periódicas para manter o sistema
 * otimizado e livre de dados desnecessários.
 */
class AgentCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:cleanup 
                            {--days=30 : Número de dias para manter arquivos temporários}
                            {--dry-run : Apenas simular, sem deletar arquivos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agente Cleanup: Limpa arquivos temporários e caches antigos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧹 Agente Cleanup iniciando limpeza...');

        try {
            $days = (int) $this->option('days');
            $dryRun = $this->option('dry-run');
            $cutoffDate = Carbon::now()->subDays($days);

            if ($dryRun) {
                $this->warn('🔍 Modo dry-run: Nenhum arquivo será deletado.');
            }

            $this->info("🗑️  Removendo arquivos temporários mais antigos que {$days} dias...");

            // TODO: Limpar arquivos temporários de coleta de dados
            // TODO: Limpar caches antigos
            // TODO: Limpar logs muito antigos (manter apenas os mais recentes)
            // TODO: Limpar arquivos de backup temporários
            // TODO: Otimizar banco de dados (se necessário)

            $cleanedItems = 0;

            // Limpeza de logs antigos
            $logsPath = storage_path('logs');
            if (is_dir($logsPath)) {
                $this->info('Limpando logs antigos...');
                $cleanedItems += $this->cleanOldLogs($logsPath, $cutoffDate, $dryRun);
            }

            // Limpeza de arquivos temporários
            $tempPath = storage_path('app/temp');
            if (is_dir($tempPath)) {
                $this->info('Limpando arquivos temporários...');
                $cleanedItems += $this->cleanTempFiles($tempPath, $cutoffDate, $dryRun);
            }

            // Limpeza de cache antigo
            $this->info('Limpando caches antigos...');
            $cleanedItems += $this->cleanCache($cutoffDate, $dryRun);

            // Limpeza de dados financeiros muito antigos (opcional - manter histórico)
            $this->info('Verificando dados financeiros antigos...');
            $cleanedItems += $this->cleanOldFinancialData($cutoffDate->copy()->subDays(90), $dryRun);

            // Limpeza de análises de sentimento muito antigas
            $this->info('Verificando análises de sentimento antigas...');
            $cleanedItems += $this->cleanOldSentimentAnalysis($cutoffDate->copy()->subDays(90), $dryRun);

            if ($dryRun) {
                $this->info("🔍 Simulação: {$cleanedItems} item(ns) seriam removidos.");
            } else {
                $this->info("✅ Limpeza concluída! {$cleanedItems} item(ns) removido(s).");
            }

            Log::info('Agent Cleanup: Limpeza concluída', [
                'items_cleaned' => $cleanedItems,
                'days' => $days,
                'timestamp' => now()
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro durante limpeza: ' . $e->getMessage());
            Log::error('Agent Cleanup: Erro durante limpeza', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Limpa logs antigos
     * 
     * @param string $path
     * @param Carbon $cutoffDate
     * @param bool $dryRun
     * @return int
     */
    private function cleanOldLogs(string $path, Carbon $cutoffDate, bool $dryRun): int
    {
        $cleanedCount = 0;

        if (!is_dir($path)) {
            return 0;
        }

        $files = glob($path . '/*.log');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = Carbon::createFromTimestamp(filemtime($file));
                
                if ($fileTime->lt($cutoffDate)) {
                    if (!$dryRun) {
                        if (unlink($file)) {
                            $cleanedCount++;
                            Log::info('Agent Cleanup: Log removido', ['file' => basename($file)]);
                        }
                    } else {
                        $cleanedCount++;
                    }
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Limpa arquivos temporários
     * 
     * @param string $path
     * @param Carbon $cutoffDate
     * @param bool $dryRun
     * @return int
     */
    private function cleanTempFiles(string $path, Carbon $cutoffDate, bool $dryRun): int
    {
        $cleanedCount = 0;

        if (!is_dir($path)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileTime = Carbon::createFromTimestamp($file->getMTime());
                
                if ($fileTime->lt($cutoffDate)) {
                    if (!$dryRun) {
                        if (unlink($file->getRealPath())) {
                            $cleanedCount++;
                        }
                    } else {
                        $cleanedCount++;
                    }
                }
            } elseif ($file->isDir() && !$dryRun) {
                // Remove diretórios vazios
                @rmdir($file->getRealPath());
            }
        }

        return $cleanedCount;
    }

    /**
     * Limpa cache antigo
     * 
     * @param Carbon $cutoffDate
     * @param bool $dryRun
     * @return int
     */
    private function cleanCache(Carbon $cutoffDate, bool $dryRun): int
    {
        try {
            if (!$dryRun) {
                // Limpa cache de aplicação
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('view:clear');
                
                return 1;
            }
            
            return 1;
        } catch (\Exception $e) {
            Log::warning('Agent Cleanup: Erro ao limpar cache', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Limpa dados financeiros muito antigos (mantém histórico recente)
     * 
     * @param Carbon $cutoffDate
     * @param bool $dryRun
     * @return int
     */
    private function cleanOldFinancialData(Carbon $cutoffDate, bool $dryRun): int
    {
        try {
            $count = \App\Models\FinancialData::where('collected_at', '<', $cutoffDate)->count();
            
            if ($count > 0 && !$dryRun) {
                \App\Models\FinancialData::where('collected_at', '<', $cutoffDate)->delete();
                Log::info('Agent Cleanup: Dados financeiros antigos removidos', ['count' => $count]);
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::warning('Agent Cleanup: Erro ao limpar dados financeiros', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Limpa análises de sentimento muito antigas
     * 
     * @param Carbon $cutoffDate
     * @param bool $dryRun
     * @return int
     */
    private function cleanOldSentimentAnalysis(Carbon $cutoffDate, bool $dryRun): int
    {
        try {
            $count = \App\Models\SentimentAnalysis::where('analyzed_at', '<', $cutoffDate)->count();
            
            if ($count > 0 && !$dryRun) {
                \App\Models\SentimentAnalysis::where('analyzed_at', '<', $cutoffDate)->delete();
                Log::info('Agent Cleanup: Análises de sentimento antigas removidas', ['count' => $count]);
            }
            
            return $count;
        } catch (\Exception $e) {
            Log::warning('Agent Cleanup: Erro ao limpar análises de sentimento', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}

