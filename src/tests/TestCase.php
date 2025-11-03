<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * TestCase - Classe base para todos os testes
 * 
 * Sistema de Agentes de IA:
 * - Júlia: coleta dados financeiros (Yahoo Finance)
 * - Pedro: análise de sentimento de mercado e mídia
 * - Key: geração de matérias financeiras usando LLM
 * - PublishNotify: notificações para revisão humana
 * - Cleanup: limpeza e manutenção do sistema
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
