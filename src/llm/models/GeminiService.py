#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Serviço para integração com Google Gemini API
Usado pelo Agente Key para gerar matérias financeiras.
"""

import os
import json
import sys
from typing import Dict, List, Optional, Any

try:
    import google.generativeai as genai
    GEMINI_AVAILABLE = True
except ImportError:
    GEMINI_AVAILABLE = False
    print("Aviso: google-generativeai não instalado. Execute: pip install google-generativeai")

from dotenv import load_dotenv

# Carrega variáveis de ambiente
load_dotenv()

def initialize_gemini():
    """
    Inicializa o cliente Gemini com a API key.
    
    Returns:
        bool: True se inicializado com sucesso, False caso contrário
    """
    if not GEMINI_AVAILABLE:
        return False
    
    api_key = os.getenv('GEMINI_API_KEY')
    if not api_key:
        return False
    
    genai.configure(api_key=api_key)
    return True

def generate_article_with_gemini(financial_data: dict, sentiment_data: dict, symbol: str) -> dict:
    """
    Gera artigo financeiro usando Google Gemini.
    
    Args:
        financial_data: Dicionário com dados financeiros
        sentiment_data: Dicionário com análise de sentimento
        symbol: Símbolo da ação
        
    Returns:
        Dicionário com 'title' e 'content'
    """
    if not initialize_gemini():
        raise ValueError("GEMINI_API_KEY não configurada")
    
    # Prepara prompt
    prompt = build_article_prompt(financial_data, sentiment_data, symbol)
    
    # Configura modelo
    model_name = os.getenv('GEMINI_MODEL', 'gemini-pro')
    model = genai.GenerativeModel(model_name)
    
    # Gera conteúdo
    try:
        response = model.generate_content(
            prompt,
            generation_config={
                'temperature': 0.6,  # Reduzido para mais objetividade jornalística, mantendo criatividade
                'max_output_tokens': 3072,  # Aumentado para permitir análises mais aprofundadas
            }
        )
        
        content = response.text
        
        # Tenta extrair JSON da resposta
        article = parse_gemini_response(content, financial_data, sentiment_data, symbol)
        return article
        
    except Exception as e:
        raise Exception(f"Erro ao gerar artigo com Gemini: {str(e)}")

def build_article_prompt(financial_data: dict, sentiment_data: dict, symbol: str) -> str:
    """
    Constrói prompt para geração de artigo com novos dados de análise.
    
    Args:
        financial_data: Dados financeiros
        sentiment_data: Dados de sentimento e percepção de marca
        symbol: Símbolo da ação
        
    Returns:
        String com o prompt
    """
    import json
    
    company_name = financial_data.get('company_name', symbol)
    
    prompt = f"""Você é um jornalista financeiro veterano com mais de 15 anos de experiência em cobertura de mercado de capitais, 
    análise fundamentalista e redação de matérias financeiras para veículos de grande circulação. Seu estilo é claro, objetivo, preciso e aprofundado, 
    transformando dados técnicos complexos em narrativas jornalísticas acessíveis e informativas.
    SUA MISSÃO COMO JORNALISTA:
        Transformar os dados brutos coletados em uma matéria jornalística profissional, aprofundada e objetiva. Você não apenas apresenta números, 
        mas os contextualiza, explica seu significado e cria uma narrativa coesa que ajuda o leitor a entender o que está acontecendo com a empresa no mercado.
    CONTEXTO:
        Você está escrevendo uma matéria sobre a ação {symbol} ({company_name}) para um portal financeiro de credibilidade. 
        A matéria será revisada por editores humanos antes da publicação e deve estar pronta para publicação após revisão.
    DADOS FINANCEIROS:
            - Preço atual: R$ {financial_data.get('price', 'N/A')}
            - Fechamento anterior: R$ {financial_data.get('previous_close', 'N/A')}
            - Variação: {financial_data.get('change', 0)} ({financial_data.get('change_percent', 0)}%)
            - Volume negociado: {financial_data.get('volume', 'N/A')}
            - Capitalização de mercado: R$ {financial_data.get('market_cap', 'N/A')}
            - P/L: {financial_data.get('pe_ratio', 'N/A')}
            - Dividend Yield: {financial_data.get('dividend_yield', 'N/A')}%
            - Máxima 52 semanas: R$ {financial_data.get('high_52w', 'N/A')}
            - Mínima 52 semanas: R$ {financial_data.get('low_52w', 'N/A')}

            ANÁLISE DE SENTIMENTO E PERCEPÇÃO DE MARCA (Agente Pedro):
            - Sentimento geral: {sentiment_data.get('sentiment', 'neutral')}
            - Score de sentimento: {sentiment_data.get('sentiment_score', 0)}
            - Notícias analisadas: {sentiment_data.get('news_count', 0)}
            - Notícias positivas: {sentiment_data.get('positive_count', 0)}
            - Notícias negativas: {sentiment_data.get('negative_count', 0)}
            - Notícias neutras: {sentiment_data.get('neutral_count', 0)}
            - Tópicos em destaque: {sentiment_data.get('trending_topics', 'N/A')}
            - Fontes de notícias: {', '.join(sentiment_data.get('news_sources', [])) if isinstance(sentiment_data.get('news_sources'), list) else sentiment_data.get('news_sources', 'N/A')}"""
    
    # Adiciona análise de mercado se disponível (do Agente Pedro)
    if sentiment_data.get('market_analysis'):
        market_analysis = sentiment_data['market_analysis']
        if isinstance(market_analysis, dict):
            market_analysis = json.dumps(market_analysis, indent=2, ensure_ascii=False)
        prompt += f"\n\nANÁLISE DE MERCADO (Agente Pedro):\n{market_analysis}"
    
    # Adiciona análise macroeconômica se disponível (do Agente Pedro)
    if sentiment_data.get('macroeconomic_analysis'):
        macro_analysis = sentiment_data['macroeconomic_analysis']
        if isinstance(macro_analysis, dict):
            macro_analysis = json.dumps(macro_analysis, indent=2, ensure_ascii=False)
        prompt += f"\n\nANÁLISE MACROECONÔMICA (Agente Pedro):\n{macro_analysis}"
    
    # Adiciona insights principais se disponíveis (do Agente Pedro)
    if sentiment_data.get('key_insights'):
        key_insights = sentiment_data['key_insights']
        if isinstance(key_insights, (list, dict)):
            key_insights = json.dumps(key_insights, indent=2, ensure_ascii=False)
        prompt += f"\n\nINSIGHTS PRINCIPAIS (Agente Pedro):\n{key_insights}"
    
    # Adiciona insights estratégicos se disponíveis (do Agente Pedro)
    if sentiment_data.get('actionable_insights') or sentiment_data.get('strategic_analysis'):
        prompt += "\n\nINSIGHTS ESTRATÉGICOS E ANÁLISE (Agente Pedro):"
        if sentiment_data.get('actionable_insights'):
            insights = sentiment_data['actionable_insights']
            if isinstance(insights, (list, dict)):
                insights = json.dumps(insights, indent=2, ensure_ascii=False)
            prompt += f"\nInsights Acionáveis: {insights}"
        if sentiment_data.get('strategic_analysis'):
            prompt += f"\nAnálise Estratégica Completa: {sentiment_data['strategic_analysis']}"
    
    # Adiciona métricas de marca e percepção se disponíveis (do Agente Pedro)
    if sentiment_data.get('brand_perception') or sentiment_data.get('engagement_metrics') or sentiment_data.get('investor_confidence'):
        prompt += "\n\nMÉTRICAS DE PERCEPÇÃO DE MARCA E COMPORTAMENTO (Agente Pedro):"
        if sentiment_data.get('brand_perception'):
            brand = sentiment_data['brand_perception']
            if isinstance(brand, dict):
                brand = json.dumps(brand, indent=2, ensure_ascii=False)
            prompt += f"\nPercepção da Marca: {brand}"
        if sentiment_data.get('engagement_metrics'):
            engagement = sentiment_data['engagement_metrics']
            if isinstance(engagement, dict):
                engagement = json.dumps(engagement, indent=2, ensure_ascii=False)
            prompt += f"\nMétricas de Engajamento: {engagement}"
        if sentiment_data.get('investor_confidence'):
            confidence = sentiment_data['investor_confidence']
            if isinstance(confidence, dict):
                confidence = json.dumps(confidence, indent=2, ensure_ascii=False)
            prompt += f"\nConfiança do Investidor: {confidence}"
        if sentiment_data.get('sentiment_breakdown'):
            breakdown = sentiment_data['sentiment_breakdown']
            if isinstance(breakdown, dict):
                breakdown = json.dumps(breakdown, indent=2, ensure_ascii=False)
            prompt += f"\nDetalhamento de Sentimento: {breakdown}"
    
    # Adiciona dados digitais e comportamentais se disponíveis (de raw_data._analysis)
    if sentiment_data.get('raw_data') and isinstance(sentiment_data['raw_data'], dict):
        analysis = sentiment_data['raw_data'].get('_analysis', {})
        if analysis.get('digital_data') or analysis.get('behavioral_data'):
            prompt += "\n\nDADOS DIGITAIS E COMPORTAMENTAIS (Agente Pedro):"
            if analysis.get('digital_data'):
                digital_data = analysis['digital_data']
                if isinstance(digital_data, dict):
                    digital_data = json.dumps(digital_data, indent=2, ensure_ascii=False)
                prompt += f"\nDados Digitais (Volume, Sentimento, Engajamento, Alcance): {digital_data}"
            if analysis.get('behavioral_data'):
                behavioral_data = analysis['behavioral_data']
                if isinstance(behavioral_data, dict):
                    behavioral_data = json.dumps(behavioral_data, indent=2, ensure_ascii=False)
                prompt += f"\nDados Comportamentais (Intenções de Compra, Reclamações, Feedback, Avaliações): {behavioral_data}"
            if analysis.get('strategic_insights'):
                strategic_insights = analysis['strategic_insights']
                if isinstance(strategic_insights, (list, dict)):
                    strategic_insights = json.dumps(strategic_insights, indent=2, ensure_ascii=False)
                prompt += f"\nInsights Estratégicos (Preço, Concorrência, Tendências, Satisfação): {strategic_insights}"
            if analysis.get('cost_optimization'):
                cost_opt = analysis['cost_optimization']
                if isinstance(cost_opt, dict):
                    cost_opt = json.dumps(cost_opt, indent=2, ensure_ascii=False)
                prompt += f"\nOtimização de Custos (Onde Cortar/Investir): {cost_opt}"
    
    # Adiciona alertas de risco se disponíveis (do Agente Pedro)
    if sentiment_data.get('risk_alerts'):
        risk_alerts = sentiment_data['risk_alerts']
        if isinstance(risk_alerts, (list, dict)):
            risk_alerts = json.dumps(risk_alerts, indent=2, ensure_ascii=False)
        prompt += f"\n\nALERTAS DE RISCO (Agente Pedro):\n{risk_alerts}"
    
    # Adiciona oportunidades de melhoria se disponíveis (do Agente Pedro)
    if sentiment_data.get('improvement_opportunities'):
        opportunities = sentiment_data['improvement_opportunities']
        if isinstance(opportunities, (list, dict)):
            opportunities = json.dumps(opportunities, indent=2, ensure_ascii=False)
        prompt += f"\n\nOPORTUNIDADES DE MELHORIA (Agente Pedro):\n{opportunities}"
    
    prompt += f""" DIRETRIZES DE REDAÇÃO JORNALÍSTICA:
            1. TÍTULO:
            - Crie um título impactante, informativo e preciso que capture a essência da matéria 
            - Evite sensacionalismo, mas seja atraente - Inclua o símbolo da ação quando relevante 
            - Exemplo: "PETR4 registra alta de 3,2% em dia de recuperação do setor petrolífero"
            
            2. INTRODUÇÃO (Lead):
            - Comece com um parágrafo forte que responda: O que está acontecendo? Por que é relevante? 
            - Contextualize a situação atual da ação no mercado 
            - Use dados concretos (preço, variação) para ancorar a narrativa 
            - Seja direto e objetivo, mas não superficial
            
            3. ANÁLISE FINANCEIRA APROFUNDADA:
            - Não apenas liste números, mas explique o que eles significam
            - Compare com médias históricas (52 semanas) quando relevante
            - Contextualize indicadores como P/L e Dividend Yield no cenário atual
            - Explique o significado do volume negociado e da capitalização de mercado
            - Use linguagem técnica quando necessário, mas sempre explique termos complexos
            - Transforme dados em insights: "O P/L de X indica...", "A variação de Y% sugere..."
            
            4. CONTEXTO DE MERCADO E SENTIMENTO:
            - Analise profundamente como o sentimento do mercado está influenciando a ação
            - Relacione as notícias recentes com os movimentos de preço
            - Explique padrões: "O sentimento positivo reflete...", "As notícias negativas indicam..."
            - Contextualize os tópicos em destaque e seu impacto
            - Se houver análise de mercado ou macroeconômica, integre-a à narrativa de forma natural
            
            5. PERCEPÇÃO DE MARCA E COMPORTAMENTO (se disponível):
            - Integre insights sobre percepção da marca de forma jornalística
            - Explique como o engajamento e a confiança do investidor se refletem nos dados
            - Use dados comportamentais para enriquecer a análise, não apenas listá-los
            - Exemplo: "A queda na confiança do investidor, medida em X pontos, coincide com..."
            
            6. INSIGHTS ESTRATÉGICOS E PERSPECTIVAS:
            - Transforme insights estratégicos em análise jornalística
            - Discuta perspectivas de forma equilibrada, baseando-se nos dados apresentados
            - Se houver alertas de risco, apresente-os de forma objetiva e contextualizada
            - Evite especulação, mas não deixe de analisar tendências identificadas nos dados
            - Seja cauteloso com projeções, sempre fundamentando em dados reais
            
            7. CONCLUSÃO:
            - Encerre com uma síntese equilibrada que reúna os principais pontos
            - Não faça recomendações explícitas de compra/venda
            - Ofereça uma visão consolidada do cenário atual
            - Deixe claro que investimentos requerem análise individual
            
            8. ESTILO E TOM JORNALÍSTICO:
            - Use linguagem profissional, clara e objetiva
            - Evite jargões desnecessários, mas use termos técnicos quando apropriado (sempre explicando)
            - Mantenha tom neutro e informativo, sem sensacionalismo
            - Seja preciso com números e dados
            - Use parágrafos curtos e objetivos para facilitar a leitura
            - Crie uma narrativa fluida que conecte os diferentes aspectos da análise
            
            9. PROFUNDIDADE E APROFUNDAMENTO:
            - Não se limite a apresentar dados, aprofunde-se em seu significado
            - Faça conexões entre diferentes indicadores e análises
            - Explique o "porquê" por trás dos números, não apenas o "o quê"
            - Use comparações e contextos históricos quando relevante
            - Transforme análises técnicas em insights compreensíveis
            
            10. FORMATO:
            - Use HTML para formatação profissional
            - Utilize <h2> para subtítulos de seções
            - Use <p> para parágrafos
            - Use <strong> para destacar dados importantes
            - Use <ul> e <li> para listas quando apropriado
            - Mantenha formatação limpa e profissional
            
            *IMPORTANTE: NÃO INCLUA O DISCLAIMER NO JSON, SERÁ ADICIONADO AUTOMATICAMENTE*
            
            IMPORTANTE - DISCLAIMER OBRIGATÓRIO:
            
            - Ao final do conteúdo, SEMPRE inclua o seguinte aviso (não inclua no JSON, será adicionado automaticamente):
            "*Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial e requer revisão humana antes da publicação. As informações apresentadas não constituem recomendação de investimento. Consulte sempre um analista financeiro certificado antes de tomar decisões de investimento.*"
            
            FORMATO DE SAÍDA:
            - Retorne APENAS um JSON válido com a seguinte estrutura:
            {{
            "title": "Título da matéria",
            "content": "Conteúdo completo em HTML formatado (sem o disclaimer, que será adicionado automaticamente)"
            }}
            - Não inclua texto adicional antes ou depois do JSON."""
    
    return prompt

def analyze_sentiment_with_gemini(articles: list, symbol: str, company_name: str, financial_data: dict = None) -> dict:
    """
    Analisa sentimento e percepção de marca usando Google Gemini.
    
    Args:
        articles: Lista de notícias
        symbol: Símbolo da ação
        company_name: Nome da empresa
        financial_data: Dados financeiros (opcional)
        
    Returns:
        Dicionário com análise completa de sentimento e percepção de marca
    """
    if financial_data is None:
        financial_data = {}
    """
    Analisa sentimento e percepção de marca usando Google Gemini.
    
    Args:
        articles: Lista de notícias
        symbol: Símbolo da ação
        company_name: Nome da empresa
        financial_data: Dados financeiros (opcional)
        
    Returns:
        Dicionário com análise completa de sentimento e percepção de marca
    """
    if not initialize_gemini():
        raise ValueError("GEMINI_API_KEY não configurada")
    
    # Prepara prompt
    prompt = build_sentiment_analysis_prompt(articles, symbol, company_name, financial_data or {})
    
    # Configura modelo
    model_name = os.getenv('GEMINI_MODEL', 'gemini-pro')
    model = genai.GenerativeModel(model_name)
    
    # Gera análise
    try:
        response = model.generate_content(
            prompt,
            generation_config={
                'temperature': 0.4,  # Balanceado para análise estratégica
                'max_output_tokens': 3072,  # Mais tokens para análise detalhada
            }
        )
        
        content = response.text
        
        # Tenta extrair JSON da resposta
        analysis = parse_sentiment_response(content, articles, symbol, company_name)
        return analysis
        
    except Exception as e:
        raise Exception(f"Erro ao analisar sentimento com Gemini: {str(e)}")

def build_sentiment_analysis_prompt(articles: list, symbol: str, company_name: str, financial_data: dict) -> str:
    """
    Constrói prompt para análise de sentimento e percepção de marca.
    
    Args:
        articles: Lista de notícias
        symbol: Símbolo da ação
        company_name: Nome da empresa
        financial_data: Dados financeiros
        
    Returns:
        String com o prompt
    """
    total_mentions = len(articles)
    
    # Prepara contexto das notícias
    news_context = "Notícias recentes:\n\n"
    for idx, article in enumerate(articles[:15], 1):
        title = article.get('title', 'Sem título')
        description = article.get('description', '')
        source = article.get('source', {}).get('name', 'Fonte desconhecida')
        published_at = article.get('publishedAt', '')
        
        news_context += f"{idx}. [{source}] {title}\n"
        if description:
            news_context += f"   {description}\n"
        if published_at:
            news_context += f"   Publicado em: {published_at}\n"
        news_context += "\n"
    
    # Prepara contexto financeiro
    if financial_data:
        financial_context = f"""Dados Financeiros Atuais:
            - Preço: {financial_data.get('price', 'N/A')}
            - Variação: {financial_data.get('change_percent', 'N/A')}%
            - Volume: {financial_data.get('volume', 'N/A')}
            - Market Cap: {financial_data.get('market_cap', 'N/A')}
            - P/L: {financial_data.get('pe_ratio', 'N/A')}
            - Dividend Yield: {financial_data.get('dividend_yield', 'N/A')}%
            - Alta 52 semanas: {financial_data.get('high_52w', 'N/A')}
            - Baixa 52 semanas: {financial_data.get('low_52w', 'N/A')}
            """
    else:
        financial_context = "Dados financeiros não disponíveis."
    
    prompt = f"""Você é um analista especializado em métricas de presença e percepção de marca, análise comportamental do público e geração de insights estratégicos acionáveis.
    
        Empresa: {company_name} (Ticker: {symbol})

        {financial_context}

        Notícias e Menções ({total_mentions} itens):
        {news_context}

        Com base nessas informações, forneça uma análise COMPLETA e ESTRATÉGICA em formato JSON com a seguinte estrutura:

        {{
        "total_mentions": {total_mentions},
        "mentions_peak": {{
            "value": número do pico de menções,
            "date": "data do pico",
            "reason": "explicação do que causou o pico (evento positivo, campanha, crise, reclamações em massa, etc)"
        }},
        "mentions_timeline": [
            {{"date": "YYYY-MM-DD", "count": número, "trend": "up|down|stable"}}
        ],
        "sentiment_breakdown": {{
            "positive_percentage": porcentagem,
            "negative_percentage": porcentagem,
            "neutral_percentage": porcentagem,
            "dominant_emotions": ["emoção1", "emoção2", "emoção3"],
            "sentiment_balance": "análise do equilíbrio entre sentimentos e o que isso revela sobre a reputação"
        }},
        "digital_data": {{
            "volume_mentions": {{
            "total": número total de menções,
            "relevance": "alta|média|baixa",
            "notoriety": "análise da relevância e notoriedade da marca no mercado"
            }},
            "sentiment_public": {{
            "positive": porcentagem,
            "negative": porcentagem,
            "neutral": porcentagem,
            "interpretation": "interpretação da percepção real da marca pelo público"
            }},
            "engagement": {{
            "clicks_estimated": número estimado,
            "shares_estimated": número estimado,
            "comments_estimated": número estimado,
            "time_spent_estimated": "tempo médio estimado",
            "engagement_score": 0-100,
            "interpretation": "interpretação do nível de interesse e profundidade das interações"
            }},
            "reach": {{
            "organic_reach_estimated": número estimado,
            "paid_reach_estimated": número estimado,
            "total_reach_estimated": número estimado,
            "reach_effectiveness": "análise da efetividade do alcance orgânico vs pago"
            }}
        }},
        "behavioral_data": {{
            "purchase_intentions": {{
            "level": "alto|médio|baixo",
            "indicators": ["indicador1", "indicador2"],
            "trend": "crescendo|estável|diminuindo",
            "interpretation": "análise das intenções de compra do público baseada nas menções e contexto"
            }},
            "complaints": {{
            "count": número estimado,
            "main_categories": [
                {{
                "category": "categoria (ex: atendimento, produto, preço)",
                "count": número,
                "severity": "alta|média|baixa"
                }}
            ],
            "trend": "crescendo|estável|diminuindo",
            "interpretation": "análise das reclamações identificadas"
            }},
            "social_feedback": {{
            "positive_feedback_count": número estimado,
            "negative_feedback_count": número estimado,
            "neutral_feedback_count": número estimado,
            "main_topics": ["tópico1", "tópico2"],
            "interpretation": "análise do feedback em redes sociais identificado nas menções"
            }},
            "product_reviews": {{
            "average_rating_estimated": 0-5,
            "review_count_estimated": número,
            "positive_reviews_percentage": porcentagem,
            "negative_reviews_percentage": porcentagem,
            "main_concerns": ["preocupação1", "preocupação2"],
            "interpretation": "análise das avaliações de produtos identificadas nas menções"
            }}
        }},
        "main_themes": [
            {{
            "theme": "nome do tema (ex: atendimento, preço, qualidade, experiência do usuário)",
            "frequency": número de menções,
            "impact": "alto|médio|baixo",
            "sentiment": "positive|negative|neutral",
            "explanation": "por que este tema está gerando impacto"
            }}
        ],
        "engagement_metrics": {{
            "estimated_reach": número estimado,
            "engagement_score": 0-100,
            "relevance_score": 0-100,
            "trust_score": 0-100,
            "interpretation": "interpretação do nível de interesse, relevância e confiança do público"
        }},
        "investor_confidence": {{
            "retention_indicators": "indicadores de retenção",
            "loyalty_indicators": "indicadores de lealdade",
            "satisfaction_indicators": "indicadores de satisfação",
            "financial_confidence": "confiança financeira baseada em dados",
            "overall_confidence_score": 0-100,
            "interpretation": "interpretação do nível de confiança geral na empresa"
        }},
        "brand_perception": {{
            "overall_perception": "positiva|negativa|neutra|mista",
            "key_strengths": ["força1", "força2"],
            "key_weaknesses": ["fraqueza1", "fraqueza2"],
            "reputation_status": "excelente|boa|regular|ruim|crítica",
            "perception_trend": "melhorando|estável|piorando"
        }},
        "strategic_insights": [
            {{
            "insight": "insight estratégico específico e acionável (ex: 'O público está mais sensível ao preço', 'O concorrente X está ganhando share', 'Há tendência de crescimento em tema Y', 'A satisfação do cliente está caindo')",
            "category": "preço|concorrência|tendência|satisfação|custos|investimento",
            "priority": "alta|média|baixa",
            "evidence": "evidências que suportam o insight",
            "recommendation": "recomendação específica e acionável"
            }}
        ],
        "cost_optimization": {{
            "areas_to_cut": [
            {{
                "area": "área onde cortar custos",
                "potential_savings": "estimativa de economia",
                "impact": "baixo|médio|alto impacto no negócio",
                "recommendation": "recomendação específica"
            }}
            ],
            "areas_to_invest": [
            {{
                "area": "área onde investir",
                "potential_return": "estimativa de retorno",
                "priority": "alta|média|baixa",
                "recommendation": "recomendação específica de investimento"
            }}
            ],
            "strategic_recommendation": "recomendação estratégica sobre onde cortar custos ou investir baseada na análise completa"
        }},
        "actionable_insights": [
            {{
            "insight": "insight acionável específico",
            "priority": "alta|média|baixa",
            "action": "ação recomendada"
            }}
        ],
        "improvement_opportunities": [
            {{
            "opportunity": "oportunidade de melhoria",
            "impact": "alto|médio|baixo",
            "feasibility": "alta|média|baixa",
            "recommendation": "recomendação específica"
            }}
        ],
        "risk_alerts": [
            {{
            "risk": "risco ou tendência emergente identificada",
            "severity": "crítica|alta|média|baixa",
            "trend": "crescendo|estável|diminuindo",
            "recommendation": "recomendação para mitigação"
            }}
        ],
        "strategic_analysis": "Análise estratégica completa e objetiva, incluindo: 
            1) Causas possíveis dos padrões identificados, 
            2) Oportunidades de melhoria, 
            3) Alertas sobre riscos ou tendências emergentes, 
            4) Recomendações sobre onde cortar custos ou investir. Seja claro, estratégico, objetivo e acionável."
        }}

        IMPORTANTE: 
        - Os insights em "strategic_insights" devem ser específicos e acionáveis, como: "O público está mais sensível ao preço", "O concorrente X está ganhando share", "Há tendência de crescimento em tema Y", "A satisfação do cliente está caindo"
        - A seção "cost_optimization" deve fornecer recomendações claras sobre onde cortar custos ou investir
        - Use dados reais das notícias e contexto financeiro para fundamentar todas as análises
        - Seja objetivo, estratégico e focado em ações práticas

        Retorne APENAS o JSON, sem markdown ou texto adicional."""
        
    return prompt

def parse_sentiment_response(content: str, articles: list, symbol: str, company_name: str) -> dict:
    """
    Parse a resposta do Gemini e extrai análise de sentimento.
    
    Args:
        content: Resposta do Gemini
        articles: Lista de artigos (para fallback)
        symbol: Símbolo da ação
        company_name: Nome da empresa
        
    Returns:
        Dicionário com análise de sentimento
    """
    try:
        # Remove markdown code blocks se presente
        content_clean = content.strip()
        if content_clean.startswith('```'):
            lines = content_clean.split('\n')
            if lines[0].startswith('```'):
                lines = lines[1:]
            if lines[-1].strip() == '```':
                lines = lines[:-1]
            content_clean = '\n'.join(lines)
        
        # Tenta encontrar JSON
        json_start = content_clean.find('{')
        json_end = content_clean.rfind('}') + 1
        
        if json_start >= 0 and json_end > json_start:
            json_str = content_clean[json_start:json_end]
            analysis = json.loads(json_str)
            
            # Adiciona campos básicos se não estiverem presentes
            if 'sentiment' not in analysis:
                # Calcula sentimento baseado em sentiment_breakdown
                sentiment_breakdown = analysis.get('sentiment_breakdown', {})
                positive = sentiment_breakdown.get('positive_percentage', 0)
                negative = sentiment_breakdown.get('negative_percentage', 0)
                
                if positive > negative + 10:
                    analysis['sentiment'] = 'positive'
                elif negative > positive + 10:
                    analysis['sentiment'] = 'negative'
                else:
                    analysis['sentiment'] = 'neutral'
            
            if 'sentiment_score' not in analysis:
                sentiment_breakdown = analysis.get('sentiment_breakdown', {})
                positive = sentiment_breakdown.get('positive_percentage', 0)
                negative = sentiment_breakdown.get('negative_percentage', 0)
                analysis['sentiment_score'] = round((positive - negative) / 100, 4)
            
            if 'news_count' not in analysis:
                analysis['news_count'] = len(articles)
            
            # Adiciona raw_data com artigos
            analysis['raw_data'] = articles
            
            return analysis
    except Exception as e:
        print(f"Erro ao parsear resposta JSON: {e}", file=sys.stderr)
    
    # Fallback: análise básica
    return get_default_sentiment_analysis(articles, symbol, company_name)

def get_default_sentiment_analysis(articles: list, symbol: str, company_name: str) -> dict:
    """
    Retorna análise de sentimento padrão (fallback).
    """
    return {
        'sentiment': 'neutral',
        'sentiment_score': 0.0,
        'news_count': len(articles),
        'positive_count': 0,
        'negative_count': 0,
        'neutral_count': len(articles),
        'trending_topics': None,
        'news_sources': [],
        'raw_data': articles,
        'total_mentions': len(articles),
        'sentiment_breakdown': {
            'positive_percentage': 0,
            'negative_percentage': 0,
            'neutral_percentage': 100,
            'dominant_emotions': [],
            'sentiment_balance': 'Análise não disponível'
        },
        'digital_data': {},
        'behavioral_data': {},
        'strategic_insights': [],
        'cost_optimization': {}
    }

def parse_gemini_response(content: str, financial_data: dict, sentiment_data: dict, symbol: str) -> dict:
    """
    Parse a resposta do Gemini e extrai título e conteúdo.
    
    Args:
        content: Resposta do Gemini
        financial_data: Dados financeiros (para fallback)
        sentiment_data: Dados de sentimento (para fallback)
        symbol: Símbolo da ação
        
    Returns:
        Dicionário com 'title' e 'content'
    """
    # Tenta extrair JSON da resposta
    try:
        # Remove markdown code blocks se presente
        content_clean = content.strip()
        if content_clean.startswith('```'):
            # Remove primeiro ``` e último ```
            lines = content_clean.split('\n')
            if lines[0].startswith('```'):
                lines = lines[1:]
            if lines[-1].strip() == '```':
                lines = lines[:-1]
            content_clean = '\n'.join(lines)
        
        # Tenta encontrar JSON
        json_start = content_clean.find('{')
        json_end = content_clean.rfind('}') + 1
        
        if json_start >= 0 and json_end > json_start:
            json_str = content_clean[json_start:json_end]
            article = json.loads(json_str)
            
            if 'title' in article and 'content' in article:
                return article
    except:
        pass
    
        # Fallback: usa o conteúdo completo como artigo
        price = financial_data.get('price', 'N/A')
        change = financial_data.get('change', 0)
        trend = 'alta' if change > 0 else ('queda' if change < 0 else 'estabilidade')
        
        title = f"Análise {symbol}: Mercado em {trend}"
        if price != 'N/A':
            title += f" - R$ {price:.2f}" if isinstance(price, (int, float)) else f" - {price}"
        
        # Adiciona disclaimer ao conteúdo
        disclaimer = "\n\n---\n\n*Este conteúdo foi gerado automaticamente com auxílio de inteligência artificial e requer revisão humana antes da publicação. As informações apresentadas não constituem recomendação de investimento. Consulte sempre um analista financeiro certificado antes de tomar decisões de investimento.*"
        content_with_disclaimer = content + disclaimer
        
        return {
            'title': title,
            'content': content_with_disclaimer
        }

if __name__ == "__main__":
    # Teste
    financial = {
        'price': 30.50,
        'change': 0.50,
        'change_percent': 1.67,
        'volume': 50000000
    }
    
    sentiment = {
        'sentiment': 'positive',
        'sentiment_score': 0.65,
        'news_count': 15
    }
    
    result = generate_article_with_gemini(financial, sentiment, 'Petrobras')
    print(json.dumps(result, indent=2, ensure_ascii=False))

