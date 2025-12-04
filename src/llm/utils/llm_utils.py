#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Utilitários para processamento de LLM
Funções auxiliares para formatação de dados e geração de conteúdo.
"""

import json
from typing import Dict, Any, Optional

def format_input_data(raw_data: Dict[str, Any]) -> Dict[str, Any]:
    """
    Formata dados brutos para processamento pelo LLM.
    
    Args:
        raw_data: Dicionário com dados financeiros e de sentimento
        
    Returns:
        Dicionário formatado e validado
    """
    formatted = {
        'company_name': raw_data.get('company_name', raw_data.get('companny_name', '')),  # Suporta ambos para compatibilidade
        'financial': {},
        'sentiment': {},
    }
    
    # Formata dados financeiros (mantém valores numéricos para cálculos)
    financial = raw_data.get('financial', {})
    if financial:
        formatted['financial'] = {
            'symbol': financial.get('action_symbol'),
            'price': financial.get('price'),
            'previous_close': financial.get('previous_close'),
            'change': financial.get('change'),
            'change_percent': financial.get('change_percent'),
            'volume': financial.get('volume'),
            'market_cap': financial.get('market_cap'),
            'pe_ratio': financial.get('pe_ratio'),
            'dividend_yield': financial.get('dividend_yield'),
            'high_52w': financial.get('high_52w'),
            'low_52w': financial.get('low_52w'),
        }
    
    # Formata dados de sentimento
    sentiment = raw_data.get('sentiment', {})
    if sentiment:
        formatted['sentiment'] = {
            'sentiment': sentiment.get('sentiment', 'neutral'),
            'sentiment_score': sentiment.get('sentiment_score', 0),
            'news_count': sentiment.get('news_count', 0),
            'positive_count': sentiment.get('positive_count', 0),
            'negative_count': sentiment.get('negative_count', 0),
            'neutral_count': sentiment.get('neutral_count', 0),
            'trending_topics': sentiment.get('trending_topics', ''),
        }
    
    return formatted

def generate_article_content(formatted_data: Dict[str, Any]) -> Dict[str, str]:
    """
    Gera conteúdo de artigo baseado em dados formatados.
    
    Por enquanto, usa template simples. Em produção, substituir por
    chamada real para LLM (Gemini).
    
    Args:
        formatted_data: Dicionário com dados formatados
        
    Returns:
        Dicionário com 'title' e 'content'
    """
    company_name = formatted_data.get('company_name', formatted_data.get('companny_name', 'N/A'))
    symbol = formatted_data.get('symbol', 'N/A')
    financial = formatted_data.get('financial', {})
    sentiment = formatted_data.get('sentiment', {})
    
    # Gera título
    price = financial.get('price')
    change = financial.get('change', 0)
    change_val = float(change) if change is not None else 0
    trend = 'alta' if change_val > 0 else ('queda' if change_val < 0 else 'estabilidade')
    
    title = f"Análise {company_name}: Mercado em {trend}"
    if price is not None:
        price_str = _format_currency(price) if isinstance(price, (int, float)) else str(price)
        title += f" - {price_str}"
    
    # Gera conteúdo
    content = f"## Análise de {company_name}\n\n"
    
    # Seção de dados financeiros
    if financial:
        content += "### Dados Financeiros\n\n"
        
        if financial.get('price'):
            price = financial['price']
            price_str = _format_currency(price) if isinstance(price, (int, float)) else str(price)
            content += f"As ações da {company_name} estão sendo negociadas a {price_str}.\n\n"
        
        if financial.get('change') and financial.get('change') != 0:
            change_val = float(financial.get('change', 0))
            change_percent = float(financial.get('change_percent', 0) or 0)
            direction = "valorização" if change_val > 0 else "desvalorização"
            content += f"A variação do dia foi de {_format_currency(abs(change_val))} ({abs(change_percent):.2f}%), "
            content += f"representando uma {direction}.\n\n"
        
        if financial.get('volume'):
            volume = financial['volume']
            volume_str = _format_number(volume) if isinstance(volume, (int, float)) else str(volume)
            content += f"O volume negociado foi de {volume_str} ações.\n\n"
    
    # Seção de análise de sentimento
    if sentiment:
        content += "### Análise de Sentimento\n\n"
        sentiment_val = sentiment.get('sentiment', 'neutral')
        news_count = sentiment.get('news_count', 0)
        sentiment_score = sentiment.get('sentiment_score', 0)
        
        sentiment_pt = {
            'positive': 'positivo',
            'negative': 'negativo',
            'neutral': 'neutro'
        }.get(sentiment_val, 'neutro')
        
        content += f"Com base na análise de {news_count} notícias, o sentimento do mercado é **{sentiment_pt}** "
        content += f"com score de {sentiment_score:.2f}.\n\n"
        
        if sentiment.get('trending_topics'):
            content += f"**Tópicos em destaque:** {sentiment['trending_topics']}\n\n"
    
    # Seção de recomendação
    content += "### Recomendação\n\n"
    recommendation = _generate_recommendation(financial, sentiment)
    content += recommendation + "\n\n"
    
    # Aviso legal
    content += "*Este conteúdo foi gerado automaticamente com auxílio de IA e requer revisão humana antes da publicação.*"
    
    return {
        'title': title,
        'content': content
    }

def _generate_recommendation(financial: Dict, sentiment: Dict) -> str:
    """
    Gera recomendação baseada em dados financeiros e sentimento.
    
    Args:
        financial: Dados financeiros
        sentiment: Dados de sentimento
        
    Returns:
        String com recomendação
    """
    recommendation = "Considerando os dados financeiros e a análise de sentimento do mercado, "
    
    change_percent = float(financial.get('change_percent', 0) or 0)
    sentiment_val = sentiment.get('sentiment', 'neutral')
    
    if sentiment_val == 'positive' and change_percent > 0:
        recommendation += "há sinais positivos, mas é importante avaliar cuidadosamente antes de investir. "
        recommendation += "Recomenda-se análise técnica e fundamentalista adicional."
    elif sentiment_val == 'negative' and change_percent < 0:
        recommendation += "há sinais de cautela. Recomenda-se aguardar mais informações ou evitar posições arriscadas. "
        recommendation += "Consulte um analista financeiro antes de tomar decisões."
    else:
        recommendation += "o mercado mostra sinais mistos. Recomenda-se acompanhar de perto e buscar mais informações antes de investir."
    
    return recommendation

def _format_currency(value: Optional[Any]) -> str:
    """Formata valor como moeda."""
    if value is None:
        return "N/A"
    try:
        return f"R$ {float(value):.2f}"
    except (ValueError, TypeError):
        return str(value)

def _format_percent(value: Optional[Any]) -> str:
    """Formata valor como percentual."""
    if value is None:
        return "N/A"
    try:
        return f"{float(value):.2f}%"
    except (ValueError, TypeError):
        return str(value)

def _format_number(value: Optional[Any]) -> str:
    """Formata número com separadores."""
    if value is None:
        return "N/A"
    try:
        num = float(value)
        return f"{num:,.0f}".replace(',', '.')
    except (ValueError, TypeError):
        return str(value)

def preprocess_data(data: Dict[str, Any]) -> Dict[str, Any]:
    """
    Preprocessa dados antes do processamento.
    
    Args:
        data: Dados brutos
        
    Returns:
        Dados preprocessados
    """
    # Remove valores None e vazios
    cleaned = {k: v for k, v in data.items() if v is not None and v != ''}
    return cleaned

def evaluate_model(model: Any, data: Dict[str, Any]) -> Dict[str, Any]:
    """
    Avalia modelo LLM (placeholder para implementação futura).
    
    Args:
        model: Modelo LLM
        data: Dados de entrada
        
    Returns:
        Resultados da avaliação
    """
    # TODO: Implementar avaliação de modelo
    return {'status': 'not_implemented'}

def save_results(results: Dict[str, Any], filepath: str) -> None:
    """
    Salva resultados em arquivo.
    
    Args:
        results: Resultados a serem salvos
        filepath: Caminho do arquivo
    """
    with open(filepath, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=4, ensure_ascii=False)

def load_model(model_path: str) -> Any:
    """
    Carrega modelo LLM (placeholder para implementação futura).
    
    Args:
        model_path: Caminho do modelo
        
    Returns:
        Modelo carregado
    """
    # TODO: Implementar carregamento de modelo
    return None