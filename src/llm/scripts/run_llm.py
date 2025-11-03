#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script para executar LLM e gerar artigos financeiros
Usado pelo Agente Key para gerar matérias baseadas em dados financeiros e análise de sentimento.

Uso: python run_llm.py <input_data_json>
"""

import json
import sys
import os
from pathlib import Path
from dotenv import load_dotenv

# Adiciona o diretório utils ao path
sys.path.insert(0, str(Path(__file__).parent.parent / 'utils'))

from llm_utils import format_input_data, generate_article_content

# Carrega variáveis de ambiente
load_dotenv()

def run_llm(input_data):
    """
    Executa o LLM para gerar artigo financeiro.
    
    Args:
        input_data: Dicionário com dados financeiros e de sentimento
        
    Returns:
        Dicionário com título e conteúdo do artigo gerado
    """
    try:
        # Formata dados de entrada
        formatted_data = format_input_data(input_data)
        
        # Gera conteúdo do artigo usando LLM
        # Por enquanto, usa template simples (substituir por LLM real quando disponível)
        article = generate_article_content(formatted_data)
        
        return article
        
    except Exception as e:
        # Retorna erro em formato JSON
        return {
            'error': str(e),
            'title': 'Erro ao gerar artigo',
            'content': f'Erro ao processar dados: {str(e)}'
        }

def main():
    """Função principal do script."""
    if len(sys.argv) != 2:
        print(json.dumps({
            'error': 'Argumentos inválidos',
            'usage': 'python run_llm.py <input_data_json>'
        }))
        sys.exit(1)
    
    try:
        # Recebe dados JSON como string
        input_json = sys.argv[1]
        input_data = json.loads(input_json)
        
        # Executa LLM
        result = run_llm(input_data)
        
        # Retorna resultado em JSON
        print(json.dumps(result, ensure_ascii=False, indent=2))
        
    except json.JSONDecodeError as e:
        print(json.dumps({
            'error': f'Erro ao decodificar JSON: {str(e)}',
            'title': 'Erro ao gerar artigo',
            'content': 'Dados de entrada inválidos'
        }))
        sys.exit(1)
    except Exception as e:
        print(json.dumps({
            'error': str(e),
            'title': 'Erro ao gerar artigo',
            'content': f'Erro inesperado: {str(e)}'
        }))
        sys.exit(1)

if __name__ == "__main__":
    main()