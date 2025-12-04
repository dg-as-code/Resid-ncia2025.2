#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script para executar LLM e gerar artigos financeiros usando Google Gemini
Usado pelo Agente Key para gerar matérias baseadas em dados financeiros e análise de sentimento.

Uso: python run_llm.py <input_data_json>
"""

import json
import sys
import os
from pathlib import Path

# Import dotenv com tratamento de erro
try:
    from dotenv import load_dotenv  # type: ignore
except ImportError:
    # Fallback caso dotenv não esteja disponível
    def load_dotenv():
        pass

# Adiciona os diretórios ao path
sys.path.insert(0, str(Path(__file__).parent.parent))
sys.path.insert(0, str(Path(__file__).parent.parent / 'models'))
sys.path.insert(0, str(Path(__file__).parent.parent / 'utils'))

# Carrega variáveis de ambiente
load_dotenv()

try:
    from models.GeminiService import generate_article_with_gemini
    GEMINI_AVAILABLE = True
except ImportError:
    GEMINI_AVAILABLE = False
    generate_article_with_gemini = None  # type: ignore
    print("Aviso: GeminiService não disponível, usando fallback", file=sys.stderr)

try:
    from utils.llm_utils import format_input_data, generate_article_content
except ImportError:
    from typing import Dict, Any
    def format_input_data(raw_data: Dict[str, Any]) -> Dict[str, Any]:
        return raw_data
    def generate_article_content(formatted_data: Dict[str, Any]) -> Dict[str, str]:
        return {'title': 'Erro', 'content': 'Utilitários não disponíveis'}

def run_llm(input_data):
    """
    Executa o LLM para gerar artigo financeiro usando Google Gemini.
    
    Args:
        input_data: Dicionário com dados financeiros e de sentimento
        {
            'company_name': 'Petrobras',
            'financial': {...},
            'sentiment': {...}
        }
        
    Returns:
        Dicionário com 'title' e 'content'
    """
    try:
        company_name = input_data.get('company_name', input_data.get('companny_name', 'N/A'))  # Suporta ambos para compatibilidade
        financial_data = input_data.get('financial', {})
        sentiment_data = input_data.get('sentiment', {})
        
        # Tenta usar Gemini se disponível
        if GEMINI_AVAILABLE and generate_article_with_gemini is not None and os.getenv('GEMINI_API_KEY'):
            try:
                result = generate_article_with_gemini(financial_data, sentiment_data, company_name)
                return result
            except Exception as e:
                print(f"Aviso: Erro ao usar Gemini, usando fallback: {e}", file=sys.stderr)
        
        # Fallback: usa template simples
        formatted_data = format_input_data(input_data)
        result = generate_article_content(formatted_data)
        return result
        
    except Exception as e:
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
        input_json = sys.argv[1]
        input_data = json.loads(input_json)
        
        result = run_llm(input_data)
        
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