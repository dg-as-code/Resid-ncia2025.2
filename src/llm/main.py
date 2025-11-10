#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Serviço LLM - Servidor para processamento de LLM
Este serviço fica em execução e pode ser chamado pelo Laravel quando necessário.
"""

import os
import sys
import json
import time
from pathlib import Path
from dotenv import load_dotenv

# Adiciona o diretório scripts ao path
sys.path.insert(0, str(Path(__file__).parent / 'scripts'))
sys.path.insert(0, str(Path(__file__).parent / 'utils'))

# Carrega variáveis de ambiente
load_dotenv()

def main():
    """Função principal do serviço LLM."""
    print("🚀 Serviço LLM iniciado...")
    print(f"📁 Diretório de trabalho: {os.getcwd()}")
    print(f"🐍 Python: {sys.version}")
    
    # Verifica se os módulos necessários estão disponíveis
    try:
        from llm_utils import format_input_data, generate_article_content
        print("✅ Módulos LLM carregados com sucesso")
    except ImportError as e:
        print(f"❌ Erro ao importar módulos: {e}")
        sys.exit(1)
    
    # Serviço em execução contínua (pode ser substituído por um servidor HTTP se necessário)
    print("⏳ Serviço LLM em execução...")
    print("💡 Este serviço pode ser chamado via scripts Python ou via Laravel")
    print("📝 Para testar, execute: python scripts/run_llm.py '{\"symbol\":\"PETR4\",...}'")
    
    # Loop simples para manter o serviço rodando
    # Em produção, você pode substituir isso por um servidor HTTP (Flask/FastAPI)
    try:
        while True:
            time.sleep(60)  # Aguarda 1 minuto antes de verificar novamente
    except KeyboardInterrupt:
        print("\n👋 Serviço LLM encerrado")
        sys.exit(0)

if __name__ == "__main__":
    main()

