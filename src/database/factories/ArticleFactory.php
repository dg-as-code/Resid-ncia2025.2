<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\FinancialData;
use App\Models\SentimentAnalysis;
use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para criar artigos/matérias jornalísticas
 * 
 * FLUXO DOS AGENTES:
 * Este factory cria artigos gerados pelo Agente Key (Redatora Veterana de Jornal Financeiro).
 * 
 * O Agente Key transforma dados técnicos em matérias jornalísticas profissionais:
 * - Dados financeiros coletados pelo Agente Júlia
 * - Análise de sentimento e opiniões da mídia do Agente Pedro
 * 
 * Status do artigo:
 * - 'pendente_revisao': Gerado pelo Agente Key, aguardando revisão humana
 * - 'aprovado': Aprovado pelo editor, pronto para publicação
 * - 'reprovado': Reprovado pelo editor (com motivo_reprovacao)
 * - 'publicado': Publicado e disponível
 * 
 * Relacionamentos:
 * - stockSymbol: Ação analisada
 * - financialData: Dados financeiros (Agente Júlia)
 * - sentimentAnalysis: Análise de sentimento (Agente Pedro)
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $symbol = $this->faker->randomElement(['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'ABEV3']);
        $stockSymbol = StockSymbol::where('symbol', $symbol)->first();
        
        if (!$stockSymbol) {
            $stockSymbol = StockSymbol::factory()->create(['symbol' => $symbol]);
        }

        // Gera conteúdo HTML (formato do Agente Key)
        $content = $this->generateArticleContent($symbol);
        
        // Extrai título do HTML (como o GeminiResponseService faz)
        $title = "Análise {$symbol}: " . $this->faker->sentence(4);
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            $title = strip_tags(trim($matches[1]));
        } elseif (preg_match('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches)) {
            $title = strip_tags(trim($matches[1]));
        }
        
        return [
            'stock_symbol_id' => $stockSymbol->id,
            'symbol' => $symbol,
            'financial_data_id' => FinancialData::factory(),
            'sentiment_analysis_id' => SentimentAnalysis::factory(),
            'title' => $title, // Título extraído do HTML (como Agente Key retorna)
            'content' => $content, // HTML formatado (output do Agente Key)
            'status' => 'pendente_revisao',
            'motivo_reprovacao' => null,
            'recomendacao' => $this->faker->randomElement([
                'Recomenda-se análise técnica e fundamentalista adicional antes de investir.',
                'Recomenda-se aguardar mais informações e contexto macroeconômico antes de tomar decisões.',
                'Recomenda-se consultar um analista financeiro certificado antes de tomar decisões de investimento.',
            ]),
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'agent' => 'key', // Agente Key (Redatora Veterana)
                'agent_version' => '1.0',
                'flow' => 'julia->pedro->key', // Fluxo dos agentes
                'format' => 'html', // Formato do conteúdo (HTML como retornado pelo Agente Key)
            ],
            'notified_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'published_at' => null,
        ];
    }

    /**
     * Gera conteúdo de artigo baseado no símbolo
     * 
     * Simula o conteúdo gerado pelo Agente Key (redatora veterana de jornal financeiro).
     * O conteúdo é em HTML formatado, como retornado pelo GeminiResponseService.
     * 
     * Este método simula o output real do Agente Key, que transforma dados técnicos
     * coletados pelo Agente Júlia e análise de sentimento do Agente Pedro em uma
     * matéria jornalística profissional, clara, objetiva e aprofundada.
     * 
     * @param string $symbol Símbolo da ação
     * @return string Conteúdo HTML do artigo (formato do Agente Key)
     */
    protected function generateArticleContent(string $symbol): string
    {
        $companyName = $this->getCompanyName($symbol);
        
        // Simula dados financeiros (como recebidos do Agente Júlia)
        $price = $this->faker->randomFloat(2, 15, 80);
        $previousClose = $price + $this->faker->randomFloat(2, -3, 3);
        $change = $price - $previousClose;
        $changePercent = ($change / $previousClose) * 100;
        $volume = $this->faker->numberBetween(10000000, 500000000);
        $marketCap = $this->faker->numberBetween(50000000000, 500000000000);
        $peRatio = $this->faker->randomFloat(2, 5, 25);
        $dividendYield = $this->faker->randomFloat(2, 2, 12);
        $high52w = $price * (1 + $this->faker->randomFloat(2, 0.1, 0.5));
        $low52w = $price * (1 - $this->faker->randomFloat(2, 0.1, 0.4));
        
        // Simula análise de sentimento (como recebida do Agente Pedro)
        $sentiment = $this->faker->randomElement(['positivo', 'negativo', 'neutro']);
        $sentimentScore = $this->faker->randomFloat(2, -1, 1);
        $newsCount = $this->faker->numberBetween(10, 50);
        $positiveCount = $sentiment === 'positivo' ? $this->faker->numberBetween(5, 20) : $this->faker->numberBetween(2, 10);
        $negativeCount = $sentiment === 'negativo' ? $this->faker->numberBetween(5, 20) : $this->faker->numberBetween(2, 10);
        $neutralCount = $newsCount - $positiveCount - $negativeCount;
        
        // Simula tópicos em destaque
        $topics = [
            'Resultados trimestrais',
            'Análise de mercado',
            'Perspectivas futuras',
            'Movimentação de investidores',
            'Análise técnica',
        ];
        $trendingTopics = $this->faker->randomElements($topics, $this->faker->numberBetween(2, 4));
        
        // Formata valores monetários
        $priceFormatted = number_format($price, 2, ',', '.');
        $previousCloseFormatted = number_format($previousClose, 2, ',', '.');
        $changeFormatted = number_format($change, 2, ',', '.');
        $changePercentFormatted = number_format(abs($changePercent), 2, ',', '.');
        $volumeFormatted = number_format($volume, 0, ',', '.');
        $marketCapFormatted = number_format($marketCap / 1000000000, 2, ',', '.') . ' bilhões';
        $high52wFormatted = number_format($high52w, 2, ',', '.');
        $low52wFormatted = number_format($low52w, 2, ',', '.');
        
        // Determina direção da variação
        $direction = $changePercent > 0 ? 'alta' : ($changePercent < 0 ? 'queda' : 'estabilidade');
        $directionClass = $changePercent > 0 ? 'positivo' : ($changePercent < 0 ? 'negativo' : 'neutro');
        
        // Gera conteúdo no estilo do Agente Key (jornalista veterana)
        $html = "<article class=\"financial-analysis\">\n\n";
        
        // Título principal (será extraído pelo parseArticleResponse)
        $html .= "<h1>{$companyName} ({$symbol}): Análise de Mercado e Perspectivas</h1>\n\n";
        
        // Introdução contextualizada
        $html .= "<p>A ação <strong>{$symbol}</strong> da <strong>{$companyName}</strong> ";
        $html .= "encerrou a sessão negociada a <strong>R\$ {$priceFormatted}</strong>, ";
        if ($changePercent != 0) {
            $html .= "registrando uma <strong>{$direction}</strong> de <strong>{$changePercentFormatted}%</strong> ";
            $html .= "em relação ao fechamento anterior de R\$ {$previousCloseFormatted}. ";
        } else {
            $html .= "mantendo <strong>estabilidade</strong> em relação ao fechamento anterior de R\$ {$previousCloseFormatted}. ";
        }
        $html .= "O volume negociado atingiu <strong>{$volumeFormatted} ações</strong>, ";
        $html .= "indicando " . ($volume > 50000000 ? "elevado" : "moderado") . " interesse dos investidores.</p>\n\n";
        
        // Seção: Dados Financeiros Principais
        $html .= "<h2>Indicadores Financeiros Principais</h2>\n\n";
        $html .= "<p>A análise dos indicadores fundamentais revela aspectos importantes sobre a avaliação da empresa:</p>\n\n";
        $html .= "<ul>\n";
        $html .= "<li><strong>Capitalização de Mercado:</strong> R\$ {$marketCapFormatted}</li>\n";
        $html .= "<li><strong>Índice P/L (Preço/Lucro):</strong> {$peRatio}x</li>\n";
        $html .= "<li><strong>Dividend Yield:</strong> {$dividendYield}%</li>\n";
        $html .= "<li><strong>Máxima de 52 semanas:</strong> R\$ {$high52wFormatted}</li>\n";
        $html .= "<li><strong>Mínima de 52 semanas:</strong> R\$ {$low52wFormatted}</li>\n";
        $html .= "</ul>\n\n";
        
        // Contextualização dos indicadores
        $html .= "<p>";
        if ($peRatio < 10) {
            $html .= "O índice P/L de {$peRatio}x sugere uma avaliação mais conservadora, ";
        } elseif ($peRatio > 20) {
            $html .= "O índice P/L de {$peRatio}x indica uma avaliação mais otimista pelo mercado, ";
        } else {
            $html .= "O índice P/L de {$peRatio}x está em linha com a média do setor, ";
        }
        $html .= "enquanto o dividend yield de {$dividendYield}% ";
        if ($dividendYield > 6) {
            $html .= "representa um atrativo significativo para investidores que buscam renda.";
        } else {
            $html .= "oferece retorno moderado aos acionistas.";
        }
        $html .= "</p>\n\n";
        
        // Seção: Análise de Sentimento do Mercado (Agente Pedro)
        $html .= "<h2>Análise de Sentimento e Percepção de Mercado</h2>\n\n";
        $html .= "<p>Com base na análise de <strong>{$newsCount} notícias</strong> coletadas e processadas, ";
        $html .= "o sentimento do mercado em relação à <strong>{$companyName}</strong> é predominantemente ";
        $html .= "<strong class=\"{$sentiment}\">{$sentiment}</strong> ";
        $html .= "(score: " . number_format($sentimentScore, 2, ',', '.') . ").</p>\n\n";
        
        $html .= "<p>A distribuição do sentimento mostra:</p>\n";
        $html .= "<ul>\n";
        $html .= "<li><strong>Notícias positivas:</strong> {$positiveCount} (" . round(($positiveCount / $newsCount) * 100, 1) . "%)</li>\n";
        $html .= "<li><strong>Notícias negativas:</strong> {$negativeCount} (" . round(($negativeCount / $newsCount) * 100, 1) . "%)</li>\n";
        $html .= "<li><strong>Notícias neutras:</strong> {$neutralCount} (" . round(($neutralCount / $newsCount) * 100, 1) . "%)</li>\n";
        $html .= "</ul>\n\n";
        
        if (!empty($trendingTopics)) {
            $html .= "<p><strong>Tópicos em destaque:</strong> " . implode(', ', $trendingTopics) . ".</p>\n\n";
        }
        
        // Análise contextualizada
        $html .= "<p>";
        if ($sentiment === 'positivo' && $sentimentScore > 0.5) {
            $html .= "A predominância de notícias positivas e o score elevado indicam um ambiente favorável ";
            $html .= "para a empresa, com expectativas otimistas do mercado em relação ao seu desempenho futuro.";
        } elseif ($sentiment === 'negativo' && $sentimentScore < -0.5) {
            $html .= "A predominância de notícias negativas e o score baixo sugerem preocupações do mercado, ";
            $html .= "requerendo atenção aos fatores que podem estar impactando a percepção sobre a empresa.";
        } else {
            $html .= "O sentimento equilibrado reflete uma visão mista do mercado, com fatores positivos e negativos ";
            $html .= "influenciando a percepção sobre a empresa de forma balanceada.";
        }
        $html .= "</p>\n\n";
        
        // Seção: Análise e Perspectivas
        $html .= "<h2>Análise e Perspectivas</h2>\n\n";
        $html .= "<p>A combinação dos indicadores financeiros e da análise de sentimento do mercado oferece uma visão ";
        $html .= "abrangente sobre a situação atual da <strong>{$companyName}</strong>. ";
        
        if ($changePercent > 0 && $sentiment === 'positivo') {
            $html .= "A valorização recente, aliada ao sentimento positivo do mercado, sugere que os investidores ";
            $html .= "estão respondendo favoravelmente às expectativas e notícias relacionadas à empresa.";
        } elseif ($changePercent < 0 && $sentiment === 'negativo') {
            $html .= "A desvalorização recente, combinada com o sentimento negativo, indica que o mercado está ";
            $html .= "reagindo a fatores que podem estar impactando a percepção sobre a empresa.";
        } else {
            $html .= "A movimentação do preço e o sentimento do mercado apresentam nuances que requerem análise mais ";
            $html .= "detalhada para compreender completamente a dinâmica atual.";
        }
        $html .= "</p>\n\n";
        
        // Recomendação (estilo jornalístico profissional)
        $html .= "<h2>Considerações Finais</h2>\n\n";
        $html .= "<p>Recomenda-se que investidores interessados em <strong>{$symbol}</strong> realizem uma análise ";
        $html .= "técnica e fundamentalista complementar, considerando não apenas os indicadores apresentados, mas também ";
        $html .= "o contexto macroeconômico, as perspectivas setoriais e os planos estratégicos da empresa. ";
        $html .= "É fundamental consultar um analista financeiro certificado antes de tomar decisões de investimento, ";
        $html .= "pois cada perfil de investidor possui objetivos e tolerância a risco específicos.</p>\n\n";
        
        // Rodapé (padrão do sistema)
        $html .= "<hr>\n\n";
        $html .= "<p class=\"disclaimer\"><em>Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial ";
        $html .= "pelo Agente Key (redatora veterana de jornal financeiro) e requer revisão humana antes da publicação. ";
        $html .= "As informações apresentadas são baseadas em dados coletados pelos Agentes Júlia (dados financeiros) e ";
        $html .= "Pedro (análise de sentimento) e não constituem recomendação de investimento. ";
        $html .= "Investidores devem realizar suas próprias análises e consultar profissionais qualificados.</em></p>\n\n";
        
        $html .= "</article>";
        
        return $html;
    }

    /**
     * Obtém nome da empresa baseado no símbolo
     * 
     * Mapeia símbolos B3 para nomes completos das empresas.
     * 
     * @param string $symbol Símbolo da ação (ex: PETR4, VALE3)
     * @return string Nome completo da empresa
     */
    protected function getCompanyName(string $symbol): string
    {
        $companies = [
            'PETR4' => 'Petrobras',
            'PETR3' => 'Petrobras',
            'VALE3' => 'Vale',
            'ITUB4' => 'Itaú Unibanco',
            'BBDC4' => 'Bradesco',
            'ABEV3' => 'Ambev',
            'WEGE3' => 'WEG',
            'RENT3' => 'Localiza',
            'MGLU3' => 'Magazine Luiza',
            'B3SA3' => 'B3',
        ];

        return $companies[$symbol] ?? $symbol;
    }

    /**
     * Estado: pendente_revisao - Gerado pelo Agente Key, aguardando revisão humana
     * 
     * Este é o estado padrão após o Agente Key gerar a matéria.
     * O Agente PublishNotify envia notificação ao editor.
     */
    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente_revisao',
            'notified_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 hour', 'now'), // 70% já notificados
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }

    /**
     * Estado: aprovado - Aprovado pelo editor, pronto para publicação
     * 
     * Artigo foi revisado e aprovado por um editor humano.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovado',
            'reviewed_at' => now()->subHours(2),
            'reviewed_by' => User::factory(),
            'published_at' => null,
        ]);
    }

    /**
     * Estado: reprovado - Reprovado pelo editor
     * 
     * Artigo foi revisado e reprovado, com motivo da reprovação.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reprovado',
            'motivo_reprovacao' => $this->faker->randomElement([
                'Conteúdo não atende aos critérios de qualidade.',
                'Informações desatualizadas ou imprecisas.',
                'Tom inadequado para o público-alvo.',
                'Falta de profundidade na análise.',
            ]),
            'reviewed_at' => now()->subHours(1),
            'reviewed_by' => User::factory(),
            'published_at' => null,
        ]);
    }

    /**
     * Estado: publicado - Publicado e disponível
     * 
     * Artigo foi aprovado e publicado.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'publicado',
            'reviewed_at' => now()->subDays(1),
            'reviewed_by' => User::factory(),
            'published_at' => now()->subHours(12),
        ]);
    }

    /**
     * Indicate that the article is for a specific symbol.
     */
    public function forSymbol(string $symbol): static
    {
        return $this->state(function (array $attributes) use ($symbol) {
            $stockSymbol = StockSymbol::where('symbol', $symbol)->first() ?? StockSymbol::factory();
            return [
                'stock_symbol_id' => $stockSymbol->id,
                'symbol' => $symbol,
            ];
        });
    }
}

