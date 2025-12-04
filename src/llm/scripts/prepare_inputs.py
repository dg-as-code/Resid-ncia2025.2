#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script para preparar dados de entrada para o LLM
Formata dados financeiros e de sentimento para processamento pelo modelo.

Uso: python prepare_inputs.py [input_file] [output_file]
"""

import json
import os
import sys
from pathlib import Path

# Adiciona o diretório utils ao path
sys.path.insert(0, str(Path(__file__).parent.parent / 'utils'))

from llm.utils.llm_utils import format_input_data

def load_input_data(file_path):
    """
    Carrega dados de entrada de um arquivo JSON.
    
    Args:
        file_path: Caminho para o arquivo JSON
        
    Returns:
        Dicionário com dados carregados
    """
    try:
        with open(file_path, 'r', encoding='utf-8') as file:
            data = json.load(file)
        return data
    except FileNotFoundError:
        print(f"Erro: Arquivo {file_path} não encontrado.", file=sys.stderr)
        sys.exit(1)
    except json.JSONDecodeError as e:
        print(f"Erro: JSON inválido em {file_path}: {str(e)}", file=sys.stderr)
        sys.exit(1)

def prepare_data(data):
    """
    Prepara dados para processamento pelo LLM.
    
    Args:
        data: Dicionário com dados brutos (financeiros e sentimento)
        
    Returns:
        Dicionário com dados formatados
    """
    # Usa função de utilitário para formatar dados
    prepared_data = format_input_data(data)
    return prepared_data

def save_prepared_data(prepared_data, output_path):
    """
    Salva dados preparados em arquivo JSON.
    
    Args:
        prepared_data: Dicionário com dados formatados
        output_path: Caminho para o arquivo de saída
    """
    try:
        with open(output_path, 'w', encoding='utf-8') as file:
            json.dump(prepared_data, file, indent=4, ensure_ascii=False)
        print(f" Dados preparados salvos em: {output_path}")
    except Exception as e:
        print(f"Erro ao salvar dados: {str(e)}", file=sys.stderr)
        sys.exit(1)

def main(input_file=None, output_file=None):
    """
    Função principal do script.
    
    Args:
        input_file: Caminho para arquivo de entrada (opcional)
        output_file: Caminho para arquivo de saída (opcional)
    """
    # Define arquivos padrão se não fornecidos
    if not input_file:
        input_file = os.getenv('INPUT_FILE', 'input.json')
    if not output_file:
        output_file = os.getenv('OUTPUT_FILE', 'prepared_output.json')
    
    print(f" Carregando dados de: {input_file}")
    data = load_input_data(input_file)
    
    print(" Preparando dados...")
    prepared_data = prepare_data(data)
    
    print(f" Salvando dados preparados em: {output_file}")
    save_prepared_data(prepared_data, output_file)

if __name__ == "__main__":
    input_file = sys.argv[1] if len(sys.argv) > 1 else None
    output_file = sys.argv[2] if len(sys.argv) > 2 else None
    main(input_file, output_file)