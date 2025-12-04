#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Agente Pedro - Análise de Sentimento de Mercado
Recebe nome da empresa, busca notícias recentes e analisa sentimento com LLM.
"""

import sys
import json
import os
import re
from typing import Dict, List, Optional, Any
from datetime import datetime, timedelta

try:
    import requests  # type: ignore
    REQUESTS_AVAILABLE = True
except ImportError:
    REQUESTS_AVAILABLE = False
    requests = None  # type: ignore
    print("Aviso: requests não instalado. Execute: pip install requests", file=sys.stderr)

try:
    from .GeminiService import analyze_sentiment_with_gemini, initialize_gemini
    GEMINI_AVAILABLE = True
except ImportError:
    try:
        from GeminiService import analyze_sentiment_with_gemini, initialize_gemini
        GEMINI_AVAILABLE = True
    except ImportError:
        GEMINI_AVAILABLE = False
        analyze_sentiment_with_gemini = None  # type: ignore
        initialize_gemini = None  # type: ignore
        print("Aviso: GeminiService não disponível. Análise básica será usada.", file=sys.stderr)

try:
    from dotenv import load_dotenv  # type: ignore
    # Carrega variáveis de ambiente
    load_dotenv()
except ImportError:
    # dotenv não está instalado, mas não é crítico
    def load_dotenv() -> None:
        pass
    load_dotenv()

# Palavras-chave para análise de sentimento
POSITIVE_WORDS = [
    'cresce', 'crescimento', 'alta', 'ganho', 'lucro', 'positivo', 'subiu', 
    'melhora', 'expansão', 'sucesso', 'vitória', 'aumento', 'valorização',
    'forte', 'robusto', 'bom', 'excelente', 'ótimo', 'superou', 'bateu recorde'
]

NEGATIVE_WORDS = [
    'queda', 'perda', 'prejuízo', 'negativo', 'caiu', 'decresce', 'crise',
    'problema', 'risco', 'derrota', 'queda', 'redução', 'desvalorização',
    'fraco', 'fraqueza', 'ruim', 'péssimo', 'falhou', 'perdeu', 'declínio'
]

def search_news(company_name: str, limit: int = 20) -> List[Dict[str, Any]]:
    """
    Busca notícias recentes sobre uma empresa.
    
    Args:
        company_name: Nome da empresa
        limit: Número máximo de notícias
        
    Returns:
        Lista de notícias encontradas
    """
    if not REQUESTS_AVAILABLE:
        return get_mock_news(company_name, limit)
    
    # Tenta usar News API se disponível
    news_api_key = os.getenv('NEWS_API_KEY')
    if news_api_key:
        return search_news_api(company_name, news_api_key, limit)
    
    # Fallback: retorna notícias mockadas
    return get_mock_news(company_name, limit)

def search_news_api(company_name: str, api_key: str, limit: int = 20) -> List[Dict[str, Any]]:
    """
    Busca notícias usando News API.
    
    Args:
        company_name: Nome da empresa
        api_key: Chave da News API
        limit: Número máximo de notícias
        
    Returns:
        Lista de notícias
    """
    try:
        url = 'https://newsapi.org/v2/everything'
        params = {
            'q': company_name,
            'language': 'pt',
            'sortBy': 'publishedAt',
            'pageSize': limit,
            'apiKey': api_key
        }
        
        if not REQUESTS_AVAILABLE or requests is None:
            return get_mock_news(company_name, limit)
        
        response = requests.get(url, params=params, timeout=10)  # type: ignore
        
        if response.status_code == 200:
            data = response.json()
            return data.get('articles', [])
        else:
            print(f"Erro na News API: {response.status_code}", file=sys.stderr)
            return get_mock_news(company_name, limit)
            
    except Exception as e:
        print(f"Erro ao buscar notícias: {e}", file=sys.stderr)
        return get_mock_news(company_name, limit)

def get_mock_news(company_name: str, limit: int = 20) -> List[Dict[str, Any]]:
    """
    Retorna notícias mockadas para desenvolvimento.
    """
    return [
        {
            'title': f"Análise: {company_name} mostra sinais positivos no mercado",
            'description': f"Especialistas indicam crescimento para {company_name}",
            'source': {'name': 'Financial News'},
            'publishedAt': datetime.now().isoformat(),
        }
    ]

def analyze_sentiment(text: str) -> float:
    """
    Analisa sentimento de um texto usando palavras-chave.
    
    Args:
        text: Texto para analisar
        
    Returns:
        Score de sentimento (-10 a 10)
    """
    text_lower = text.lower()
    
    positive_count = sum(1 for word in POSITIVE_WORDS if word in text_lower)
    negative_count = sum(1 for word in NEGATIVE_WORDS if word in text_lower)
    
    total = positive_count + negative_count
    if total == 0:
        return 0.0
    
    score = (positive_count - negative_count) / total
    return round(score, 4)

def analyze_news_sentiment(articles: List[Dict[str, Any]]) -> Dict[str, Any]:
    """
    Analisa sentimento de uma lista de notícias.
    
    Args:
        articles: Lista de notícias
        
    Returns:
        Dicionário com análise de sentimento
    """
    if not articles:
        return get_default_sentiment()
    
    positive_count = 0
    negative_count = 0
    neutral_count = 0
    total_score = 0.0
    trending_topics = []
    sources = []
    
    for article in articles:
        title = article.get('title', '')
        description = article.get('description', '')
        content = f"{title} {description}".lower()
        
        score = analyze_sentiment(content)
        total_score += score
        
        if score > 0.5:
            positive_count += 1
        elif score < -0.5:
            negative_count += 1
        else:
            neutral_count += 1
        
        # Coleta fontes
        source = article.get('source', {}).get('name', 'Desconhecido')
        if source not in sources:
            sources.append(source)
        
        # Extrai palavras-chave (simples)
        words = re.findall(r'\b\w{5,}\b', content)
        trending_topics.extend(words[:5])
    
    avg_score = total_score / len(articles) if articles else 0.0
    
    # Determina sentimento geral
    if avg_score > 0.5:
        sentiment = 'positive'
    elif avg_score < -0.5:
        sentiment = 'negative'
    else:
        sentiment = 'neutral'
    
    # Remove duplicatas e limita trending topics
    trending_topics = list(set(trending_topics))[:10]
    
    return {
        'sentiment': sentiment,
        'sentiment_score': round(avg_score, 4),
        'news_count': len(articles),
        'positive_count': positive_count,
        'negative_count': negative_count,
        'neutral_count': neutral_count,
        'trending_topics': ', '.join(trending_topics) if trending_topics else None,
        'news_sources': sources,
        'raw_data': articles,
        'analyzed_at': datetime.now().isoformat()
    }

def get_default_sentiment() -> Dict[str, Any]:
    """
    Retorna sentimento padrão (neutro).
    """
    return {
        'sentiment': 'neutral',
        'sentiment_score': 0.0,
        'news_count': 0,
        'positive_count': 0,
        'negative_count': 0,
        'neutral_count': 0,
        'trending_topics': None,
        'news_sources': [],
        'raw_data': [],
        'analyzed_at': datetime.now().isoformat()
    }

def analyze_company_sentiment(company_name: str, limit: int = 20, symbol: str = '', financial_data: dict = {}) -> Dict[str, Any]:
    """
    Função principal: analisa sentimento de mercado, da marca e opiniões da mídia sobre uma empresa com LLM.
    
    Args:
        company_name: Nome da empresa
        limit: Número máximo de notícias para analisar
        symbol: Símbolo da ação (opcional)
        financial_data: Dados financeiros 
    
    Returns:
        Dicionário com análise completa de sentimento, percepção de marca e opniões da mídia 
    """
    # Busca notícias
    articles = search_news(company_name, limit)
    
    # Tenta usar LLM para análise avançada
    if GEMINI_AVAILABLE and initialize_gemini is not None and analyze_sentiment_with_gemini is not None:
        try:
            # Verifica se Gemini foi inicializado com sucesso
            if not initialize_gemini():
                raise ValueError("Gemini não pôde ser inicializado")
            
            # Usa LLM para análise completa
            analysis = analyze_sentiment_with_gemini(
                articles,
                symbol or company_name,
                company_name,
                financial_data
            )
            
            # Adiciona campos básicos se não estiverem presentes
            if 'company_name' not in analysis:
                analysis['company_name'] = company_name
            if 'symbol' not in analysis:
                analysis['symbol'] = symbol or company_name
            
            # Garante que raw_data contém os artigos
            if 'raw_data' not in analysis or not analysis['raw_data']:
                analysis['raw_data'] = articles
            
            # Adiciona estrutura _analysis se necessário
            raw_data_value = analysis.get('raw_data')
            if isinstance(raw_data_value, list) and len(raw_data_value) > 0:
                # raw_data é array de artigos, adiciona _analysis
                raw_data_dict: Dict[str, Any] = {
                    '_analysis': {
                        'digital_data': analysis.get('digital_data', {}),
                        'behavioral_data': analysis.get('behavioral_data', {}),
                        'strategic_insights': analysis.get('strategic_insights', []),
                        'cost_optimization': analysis.get('cost_optimization', {})
                    },
                    'articles': raw_data_value  # Preserva artigos
                }
                analysis['raw_data'] = raw_data_dict
            elif isinstance(raw_data_value, dict):
                # raw_data já é um dicionário, apenas adiciona _analysis se não existir
                if '_analysis' not in raw_data_value:
                    raw_data_value['_analysis'] = {
                        'digital_data': analysis.get('digital_data', {}),
                        'behavioral_data': analysis.get('behavioral_data', {}),
                        'strategic_insights': analysis.get('strategic_insights', []),
                        'cost_optimization': analysis.get('cost_optimization', {})
                    }
            
            return analysis
        except Exception as e:
            print(f"Erro na análise com LLM: {e}. Usando análise básica.", file=sys.stderr)
    
    # Fallback: análise básica sem LLM
    analysis = analyze_news_sentiment(articles)
    analysis['company_name'] = company_name
    analysis['symbol'] = symbol or company_name
    
    # Adiciona estrutura básica para compatibilidade
    analysis['raw_data'] = {
        'articles': articles,
        '_analysis': {
            'digital_data': {},
            'behavioral_data': {},
            'strategic_insights': [],
            'cost_optimization': {}
        }
    }
    
    return analysis

def main():
    """
    Função principal - pode ser chamada via linha de comando.
    Uso: python AgentPedro.py <company_name> [limit] [symbol] [financial_data_json]
    """
    if len(sys.argv) < 2:
        company_name = "Petrobras"
        print(f"Nenhuma empresa fornecida, usando exemplo: {company_name}", file=sys.stderr)
    else:
        company_name = sys.argv[1]
    
    limit = int(sys.argv[2]) if len(sys.argv) > 2 else 20
    symbol = sys.argv[3] if len(sys.argv) > 3 else company_name
    
    financial_data = {}
    if len(sys.argv) > 4:
        try:
            financial_data = json.loads(sys.argv[4])
        except:
            pass
    
    try:
        analysis = analyze_company_sentiment(company_name, limit, symbol, financial_data)
        print(json.dumps(analysis, indent=2, ensure_ascii=False))
        return 0
    except Exception as e:
        error = {
            'error': str(e),
            'company_name': company_name,
            'symbol': symbol
        }
        print(json.dumps(error, indent=2, ensure_ascii=False), file=sys.stderr)
        return 1

if __name__ == "__main__":
    sys.exit(main())

