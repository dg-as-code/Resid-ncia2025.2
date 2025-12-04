<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Matéria Pendente de Revisão</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .article {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
        }
        .article-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .article-meta {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nova Matéria Pendente de Revisão</h1>
    </div>
    
    <div class="content">
        <p>Olá,</p>
        
        <p>Uma nova matéria foi gerada e está aguardando sua revisão:</p>
        
        <div class="article">
            <div class="article-title">{{ $article->title }}</div>
            <div class="article-meta">
                <strong>Símbolo:</strong> {{ $article->symbol }}<br>
                <strong>Criado em:</strong> {{ $article->created_at->format('d/m/Y H:i') }}<br>
                @if($analysis)
                <strong>Análise ID:</strong> {{ $analysis->id }}
                @endif
            </div>
            <p style="margin-top: 10px;">
                <a href="{{ url('/api/articles/' . $article->id) }}" class="button">Ver Matéria</a>
            </p>
        </div>
        
        <p>Por favor, acesse o sistema para revisar e aprovar ou reprovar esta matéria.</p>
    </div>
    
    <div class="footer">
        <p>Este é um email automático do Sistema de Agentes de IA.</p>
        <p>Não responda a este email.</p>
    </div>
</body>
</html>

