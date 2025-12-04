#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Serviço LLM - Servidor para processamento de LLM

FLUXO DOS AGENTES:
Este serviço fornece suporte Python para os agentes de IA quando necessário.

Fluxo Principal (via PHP):
- Agente Júlia: Usa YahooFinanceService (Gemini API) diretamente via PHP
- Agente Pedro: Usa NewsAnalysisService (Gemini API) diretamente via PHP
- Agente Key: Usa GeminiResponseService (Gemini API) diretamente via PHP

Fluxo Alternativo (via Python - Fallback):
- Agente Júlia: Pode usar AgentJulia.py quando Gemini não está disponível
- Agente Pedro: Pode usar AgentPedro.py para análise de sentimento
- Agente Key: Pode usar run_llm.py quando GeminiResponseService falha

Scripts Python Disponíveis:
- llm/models/AgentJulia.py: Coleta dados financeiros (fallback para Agente Júlia)
- llm/models/AgentPedro.py: Análise de sentimento (fallback para Agente Pedro)
- llm/scripts/run_llm.py: Geração de artigos (fallback para Agente Key)

NOTA: Este serviço fica em execução contínua e pode ser usado como fallback
quando a integração direta via PHP (Gemini API) não está disponível ou falha.
"""

import os
import sys
import json
import time
from pathlib import Path
from typing import Optional

# Import dotenv com tratamento de erro
try:
    from dotenv import load_dotenv
except ImportError:
    def load_dotenv():
        pass

# Adiciona o diretório scripts ao path
sys.path.insert(0, str(Path(__file__).parent / 'scripts'))
sys.path.insert(0, str(Path(__file__).parent / 'models'))
sys.path.insert(0, str(Path(__file__).parent / 'utils'))

# Carrega variáveis de ambiente
load_dotenv()


def check_modules() -> bool:
    """
    Verifica se os módulos necessários estão disponíveis.
    
    Returns:
        bool: True se todos os módulos estão disponíveis
    """
    try:
        # Verifica módulos principais
        from models.GeminiService import generate_article_with_gemini
        print("✅ GeminiService disponível")
    except ImportError:
        print("⚠️  GeminiService não disponível (Gemini API pode não estar configurada)")
    
    try:
        from utils.llm_utils import format_input_data, generate_article_content
        print("✅ Utilitários LLM carregados com sucesso")
        return True
    except ImportError as e:
        print(f"❌ Erro ao importar módulos: {e}")
        return False


def check_agent_scripts() -> dict:
    """
    Verifica se os scripts dos agentes estão disponíveis.
    
    Returns:
        dict: Status de cada script
    """
    scripts_status = {
        'AgentJulia': False,
        'AgentPedro': False,
        'run_llm': False,
    }
    
    base_path = Path(__file__).parent
    
    # Verifica AgentJulia.py
    julia_path = base_path / 'models' / 'AgentJulia.py'
    if julia_path.exists():
        scripts_status['AgentJulia'] = True
        print(f"✅ AgentJulia.py disponível: {julia_path}")
    else:
        print(f"⚠️  AgentJulia.py não encontrado: {julia_path}")
    
    # Verifica AgentPedro.py
    pedro_path = base_path / 'models' / 'AgentPedro.py'
    if pedro_path.exists():
        scripts_status['AgentPedro'] = True
        print(f"✅ AgentPedro.py disponível: {pedro_path}")
    else:
        print(f"⚠️  AgentPedro.py não encontrado: {pedro_path}")
    
    # Verifica run_llm.py
    run_llm_path = base_path / 'scripts' / 'run_llm.py'
    if run_llm_path.exists():
        scripts_status['run_llm'] = True
        print(f"✅ run_llm.py disponível: {run_llm_path}")
    else:
        print(f"⚠️  run_llm.py não encontrado: {run_llm_path}")
    
    return scripts_status


def main():
    """
    Função principal do serviço LLM.
    
    Este serviço fica em execução contínua e pode ser usado como fallback
    quando a integração direta via PHP (Gemini API) não está disponível.
    """
    print("=" * 70)
    print("🚀 Serviço LLM - Suporte para Agentes de IA")
    print("=" * 70)
    print(f"📁 Diretório de trabalho: {os.getcwd()}")
    print(f"🐍 Python: {sys.version.split()[0]}")
    print()
    
    # Verifica módulos
    print("📦 Verificando módulos...")
    modules_ok = check_modules()
    print()
    
    # Verifica scripts dos agentes
    print("📜 Verificando scripts dos agentes...")
    scripts_status = check_agent_scripts()
    print()
    
    # Verifica configuração do Gemini
    gemini_key = os.getenv('GEMINI_API_KEY')
    if gemini_key:
        print(f"✅ GEMINI_API_KEY configurado (primeiros 10 caracteres: {gemini_key[:10]}...)")
    else:
        print("⚠️  GEMINI_API_KEY não configurado (scripts Python serão usados como fallback)")
    print()
    
    if not modules_ok:
        print("❌ Erro: Módulos essenciais não disponíveis")
        print("   O serviço pode não funcionar corretamente")
        print()
    
    # Informações sobre o fluxo
    print("=" * 70)
    print("🔄 FLUXO DOS AGENTES")
    print("=" * 70)
    print("Fluxo Principal (via PHP - Gemini API direta):")
    print("  1. Agente Júlia → YahooFinanceService (Gemini API)")
    print("  2. Agente Pedro → NewsAnalysisService (Gemini API)")
    print("  3. Agente Key → GeminiResponseService (Gemini API)")
    print()
    print("Fluxo Alternativo (via Python - Fallback):")
    print("  1. Agente Júlia → AgentJulia.py (se Gemini falhar)")
    print("  2. Agente Pedro → AgentPedro.py (se Gemini falhar)")
    print("  3. Agente Key → run_llm.py (se Gemini falhar)")
    print()
    print("=" * 70)
    print("📝 Scripts Python Disponíveis:")
    print("=" * 70)
    print("  • AgentJulia.py: Coleta dados financeiros")
    print("    Uso: python llm/models/AgentJulia.py 'Petrobras'")
    print()
    print("  • AgentPedro.py: Análise de sentimento")
    print("    Uso: python llm/models/AgentPedro.py 'Petrobras' 20 'PETR4'")
    print()
    print("  • run_llm.py: Geração de artigos")
    print("    Uso: python llm/scripts/run_llm.py '{\"symbol\":\"PETR4\",...}'")
    print()
    print("=" * 70)
    print("🔄 Serviço em execução contínua...")
    print("=" * 70)
    print("Este serviço NÃO deve encerrar após consultas")
    print("Para encerrar, use Ctrl+C ou pare o container Docker")
    print("=" * 70)
    print()
    
    try:
        heartbeat_count = 0
        while True:
            heartbeat_count += 1
            # Heartbeat a cada 10 minutos
            if heartbeat_count % 10 == 0:
                timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
                print(f"[{timestamp}] 💓 Serviço LLM ativo (heartbeat #{heartbeat_count})")
            time.sleep(60)  # Aguarda 1 minuto antes de verificar novamente
    except KeyboardInterrupt:
        print()
        print("=" * 70)
        print("🛑 Serviço LLM encerrado pelo usuário")
        print("=" * 70)
        sys.exit(0)
    except Exception as e:
        print()
        print("=" * 70)
        print(f"❌ Erro inesperado no serviço LLM: {e}")
        print("=" * 70)
        print("Reiniciando em 5 segundos...")
        time.sleep(5)
        # Não encerra, apenas loga o erro e continua
        pass

if __name__ == "__main__":
    main()

