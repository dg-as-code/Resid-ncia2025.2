<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder - Seeder principal da aplicação
 * 
 * Popula o banco de dados com dados iniciais para os agentes de IA.
 * Execute com: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seeders dos agentes de IA
        $this->call([
            StockSymbolSeeder::class,
        ]);

        // Seeders existentes (mantidos para compatibilidade)
        // $this->call([
        //     AgendSeeder::class,
        // ]);

        // Criar usuários de teste (opcional)
        // \App\Models\User::factory(10)->create();
    }
}
