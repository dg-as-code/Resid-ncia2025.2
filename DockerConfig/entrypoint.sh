#!/usr/bin/env bash

#--------------------------------------------------------------------------
# Entrypoint Script - Laravel Application
#--------------------------------------------------------------------------
#
# Este script é executado quando o container Docker é iniciado.
# Ele configura e inicia os serviços necessários para a aplicação Laravel.
#
# Sistema de Agentes de IA:
# - Júlia: coleta dados financeiros (Yahoo Finance + Gemini API)
# - Pedro: análise de sentimento de mercado e mídia (News API)
# - Key: geração de matérias financeiras usando LLM (Python/Gemini)
# - PublishNotify: notificações para revisão humana
# - Cleanup: limpeza e manutenção do sistema
#

set -e  # Sair imediatamente se um comando falhar

# Aguardar o banco de dados estar pronto (opcional, mas recomendado)
echo "Aguardando serviços estarem prontos..."

# Iniciar Nginx
echo "Iniciando Nginx..."
service nginx start

# Aguardar um momento para o Nginx iniciar completamente
sleep 1

echo "Nginx iniciado com sucesso"

# Iniciar PHP-FPM em foreground (para manter o container rodando)
echo "Iniciando PHP-FPM..."
echo "Sistema configurado para execução contínua - não encerrará após consultas"
echo "Para parar o sistema, use: docker-compose down"

# Executa PHP-FPM em foreground para manter o container ativo
# O 'exec' substitui o processo atual, mantendo o container vivo
exec php-fpm