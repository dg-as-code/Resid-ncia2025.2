<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matérias Pendentes de Revisão</title>
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
        .count-badge {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        .article {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
        }
        .article-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .article-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
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
        <h1>Matérias Pendentes de Revisão</h1>
    </div>
    
    <div class="content">
        <p>Olá,</p>
        
        <p>
            Existem <strong>{{ $count }}</strong> matéria(s) pendente(s) de revisão:
            <span class="count-badge">{{ $count }}</span>
        </p>
        
        @foreach($articles as $article)
        <div class="article">
            <div class="article-title">{{ $article->title }}</div>
            <div class="article-meta">
                <strong>ID:</strong> {{ $article->id }} | 
                <strong>Símbolo:</strong> {{ $article->symbol }} | 
                <strong>Criado em:</strong> {{ $article->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
        @endforeach
        
        <p style="margin-top: 20px;">
            <a href="{{ url('/api/articles?status=pendente_revisao') }}" class="button">Ver Todas as Matérias Pendentes</a>
        </p>
        
        <p>Por favor, acesse o sistema para revisar e aprovar ou reprovar estas matérias.</p>
    </div>
    
    <div class="footer">
        <p>Este é um email automático do Sistema de Agentes de IA.</p>
        <p>Não responda a este email.</p>
    </div>
</body>
</html>

