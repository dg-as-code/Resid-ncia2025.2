#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Agente Júlia - Coleta de Dados Financeiros
Recebe company_name (nome da empresa, serviço ou produto) como argumento e retorna JSON estruturado com dados financeiros.
Busca automaticamente o ticker correspondente ao nome fornecido.
"""

import sys
import json
import time
try:
    import yfinance as yf  # type: ignore
except ImportError:
    raise ImportError("yfinance não está instalado. Execute: pip install yfinance>=0.2.0")
from typing import Dict, Optional, Any

def search_ticker_by_name(company_name: str) -> Optional[str]:
    """
    Busca o ticker de uma ação a partir do nome da empresa.
    Tenta diferentes estratégias para encontrar o ticker correto.
    
    Args:
        company_name: Nome da empresa, serviço ou produto
        
    Returns:
        Ticker encontrado ou None
    """
    try:
        # Estratégia 1: Se já parece um ticker (curto, alfanumérico), tenta usar diretamente
        if len(company_name) <= 10 and company_name.replace('.', '').replace('-', '').isalnum():
            potential_ticker = company_name.upper()
            if not potential_ticker.endswith('.SA') and len(potential_ticker) <= 6:
                potential_ticker = f"{potential_ticker}.SA"
            
            try:
                test_stock = yf.Ticker(potential_ticker)
                test_info = test_stock.info
                if test_info and test_info.get('longName'):
                    return potential_ticker
            except:
                pass
        
        # Estratégia 2: Tenta formatar o nome como ticker brasileiro comum
        # Remove espaços e caracteres especiais, pega primeiras letras
        name_clean = ''.join(c.upper() for c in company_name if c.isalnum())[:4]
        
        if len(name_clean) >= 3:
            # Tenta sufixos comuns brasileiros
            for suffix in ['4', '3', '11', '5']:
                potential_ticker = f"{name_clean}{suffix}.SA"
                try:
                    test_stock = yf.Ticker(potential_ticker)
                    test_info = test_stock.info
                    if test_info and test_info.get('longName'):
                        # Verifica se o nome corresponde (busca parcial)
                        long_name = test_info.get('longName', '').upper()
                        short_name = test_info.get('shortName', '').upper()
                        company_upper = company_name.upper()
                        
                        # Verifica correspondência parcial
                        if (any(word in long_name for word in company_upper.split() if len(word) > 3) or
                            any(word in company_upper for word in long_name.split() if len(word) > 3)):
                            return potential_ticker
                except:
                    continue
        
        # Estratégia 3: Tenta usar o nome diretamente (alguns nomes podem funcionar)
        try:
            test_stock = yf.Ticker(company_name)
            test_info = test_stock.info
            if test_info and test_info.get('symbol'):
                return test_info.get('symbol')
        except:
            pass
        
        return None
        
    except Exception as e:
        print(f"Erro ao buscar ticker para {company_name}: {e}", file=sys.stderr)
        return None

def get_stock_data_by_company_name(company_name: str) -> Optional[Dict[str, Any]]:
    """
    Obtém dados financeiros usando o nome da empresa.
    Primeiro busca o ticker, depois obtém os dados.
    
    Args:
        company_name: Nome da empresa, serviço ou produto
        
    Returns:
        Dicionário com dados financeiros ou None
    """
    # Primeiro, tenta encontrar o ticker
    ticker = search_ticker_by_name(company_name)
    
    if not ticker:
        # Se não encontrar ticker, tenta usar o nome diretamente
        # (algumas empresas podem ser encontradas pelo nome)
        ticker = company_name
    
    # Agora busca os dados usando o ticker encontrado
    return get_stock_data(ticker, company_name)

def get_stock_data(ticker: str, company_name: Optional[str] = None) -> Optional[Dict[str, Any]]:
    """
    Obtém os dados financeiros de uma ação usando Yahoo Finance.
    
    Args:
        ticker: Símbolo da ação (ex: Petrobras.SA, Petrobras, AAPL)
        
    Returns:
        Dicionário com dados financeiros ou None em caso de erro
    """
    try:
        # Adiciona .SA se for ação brasileira sem sufixo
        if not ticker.endswith('.SA') and len(ticker) <= 6:
            ticker_formatted = f"{ticker}.SA"
        else:
            ticker_formatted = ticker
        
        stock = yf.Ticker(ticker_formatted)
        info = stock.info
        
        # Obtém dados históricos para calcular variação
        hist = stock.history(period="2d")
        
        if info is None or info == {}:
            return None
        
        # Extrai e estrutura os dados principais
        current_price = info.get('currentPrice') or info.get('regularMarketPrice') or hist['Close'].iloc[-1] if not hist.empty else None
        previous_close = info.get('previousClose') or hist['Close'].iloc[-2] if len(hist) > 1 else current_price
        
        change = None
        change_percent = None
        if current_price and previous_close:
            change = current_price - previous_close
            change_percent = (change / previous_close) * 100 if previous_close > 0 else 0
        
        # Extrai informações da empresa (além dos dados financeiros)
        company_info = {
            'name': info.get('longName') or info.get('shortName') or ticker,
            'short_name': info.get('shortName'),
            'sector': info.get('sector'),
            'industry': info.get('industry'),
            'description': info.get('longBusinessSummary') or info.get('description'),
            'website': info.get('website'),
            'country': info.get('country'),
            'city': info.get('city'),
            'state': info.get('state'),
            'address': info.get('address1'),
            'phone': info.get('phone'),
            'employees': info.get('fullTimeEmployees'),
            'founded': info.get('founded'),
            'ceo': info.get('ceo'),
            'exchange': info.get('exchange', 'SAO'),
            'currency': info.get('currency', 'BRL'),
        }
        
        # Extrai dados financeiros adicionais
        financial_metrics = {
            'revenue': info.get('totalRevenue') or info.get('revenue'),
            'revenue_growth': info.get('revenueGrowth'),
            'gross_profit': info.get('grossProfits'),
            'operating_income': info.get('operatingIncome'),
            'net_income': info.get('netIncomeToCommon') or info.get('netIncome'),
            'ebitda': info.get('ebitda'),
            'total_assets': info.get('totalAssets'),
            'total_liabilities': info.get('totalLiab'),
            'total_cash': info.get('totalCash'),
            'total_debt': info.get('totalDebt'),
            'book_value': info.get('bookValue'),
            'price_to_book': info.get('priceToBook'),
            'earnings_growth': info.get('earningsGrowth'),
            'revenue_per_share': info.get('revenuePerShare'),
            'earnings_per_share': info.get('trailingEps') or info.get('forwardEps'),
            'profit_margin': info.get('profitMargins'),
            'operating_margin': info.get('operatingMargins'),
            'return_on_equity': info.get('returnOnEquity'),
            'return_on_assets': info.get('returnOnAssets'),
        }
        
        # Extrai dados de dividendos
        dividend_info = {
            'dividend_rate': info.get('dividendRate'),
            'dividend_yield': float(info.get('dividendYield', 0) or 0) * 100 if info.get('dividendYield') else None,
            'payout_ratio': info.get('payoutRatio'),
            'ex_dividend_date': info.get('exDividendDate'),
            'dividend_date': info.get('dividendDate'),
            'five_year_avg_dividend_yield': info.get('fiveYearAvgDividendYield'),
        }
        
        # Extrai indicadores de crescimento
        growth_metrics = {
            'revenue_growth': info.get('revenueGrowth'),
            'earnings_growth': info.get('earningsGrowth'),
            'earnings_quarterly_growth': info.get('earningsQuarterlyGrowth'),
            'revenue_quarterly_growth': info.get('revenueQuarterlyGrowth'),
        }
        
        # Se company_name foi fornecido e é diferente do nome encontrado, usa o fornecido
        final_company_name = company_name if company_name else company_info['name']
        
        # Estrutura dados em formato JSON padronizado
        structured_data = {
            # Identificação
            'symbol': info.get('symbol') or ticker,
            'company_name': final_company_name,
            'searched_name': company_name,  # Nome original pesquisado
            
            # Dados de preço e mercado
            'price': float(current_price) if current_price else None,
            'previous_close': float(previous_close) if previous_close else None,
            'change': float(change) if change is not None else None,
            'change_percent': float(change_percent) if change_percent is not None else None,
            'volume': int(info.get('volume', 0) or info.get('regularMarketVolume', 0)),
            'market_cap': int(info.get('marketCap', 0) or 0),
            'high_52w': float(info.get('fiftyTwoWeekHigh', 0) or 0),
            'low_52w': float(info.get('fiftyTwoWeekLow', 0) or 0),
            
            # Indicadores de avaliação
            'pe_ratio': float(info.get('trailingPE', 0) or info.get('forwardPE', 0) or 0),
            'price_to_book': financial_metrics.get('price_to_book'),
            'peg_ratio': info.get('pegRatio'),
            'enterprise_value': info.get('enterpriseValue'),
            'enterprise_to_revenue': info.get('enterpriseToRevenue'),
            'enterprise_to_ebitda': info.get('enterpriseToEbitda'),
            
            # Informações da empresa
            'company_info': {k: v for k, v in company_info.items() if v is not None},
            
            # Dados financeiros
            'financial_metrics': {k: v for k, v in financial_metrics.items() if v is not None},
            
            # Dividendos
            'dividend_info': {k: v for k, v in dividend_info.items() if v is not None},
            
            # Crescimento
            'growth_metrics': {k: v for k, v in growth_metrics.items() if v is not None},
            
            # Metadados
            'currency': company_info['currency'],
            'exchange': company_info['exchange'],
            'raw_data': info,  # Mantém dados brutos completos para referência
            'collected_at': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        return structured_data
        
    except Exception as e:
        print(f"Erro ao obter dados financeiros para {ticker}: {e}", file=sys.stderr)
        return None

def get_stock_data_with_retry(company_name: str, max_retries: int = 3, delay: int = 5) -> Optional[Dict[str, Any]]:
    """
    Obtém os dados financeiros usando o nome da empresa com retry.
    
    Args:
        company_name: Nome da empresa, serviço ou produto
        max_retries: Número máximo de tentativas
        delay: Delay entre tentativas (segundos)
        
    Returns:
        Dicionário com dados financeiros ou None
    """
    for attempt in range(max_retries):
        data = get_stock_data_by_company_name(company_name)
        if data is not None:
            return data
        if attempt < max_retries - 1:
            time.sleep(delay)
    return None

def main():
    """
    Função principal - pode ser chamada via linha de comando.
    Uso: python AgentJulia.py <company_name>
    
    Args:
        company_name: Nome da empresa, serviço contratado ou produto
                     Exemplos: "Petrobras", "Petróleo Brasileiro", "Petrobras", "Apple Inc"
    """
    if len(sys.argv) < 2:
        # Se não houver argumentos, usa exemplo padrão
        company_name = "Petrobras"
        print(f"Nenhum nome de empresa fornecido, usando exemplo: {company_name}", file=sys.stderr)
    else:
        company_name = sys.argv[1]
    
    data = get_stock_data_with_retry(company_name)
    
    if data:
        # Retorna JSON para stdout
        print(json.dumps(data, indent=2, ensure_ascii=False))
        return 0
    else:
        error = {
            'error': f'Não foi possível obter dados para "{company_name}"',
            'company_name': company_name,
            'suggestion': 'Verifique se o nome da empresa está correto ou tente usar o ticker diretamente'
        }
        print(json.dumps(error, indent=2, ensure_ascii=False), file=sys.stderr)
        return 1

if __name__ == "__main__":
    sys.exit(main())