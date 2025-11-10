# 🚀 Inicialização Completa do Projeto

## ✅ Análise e Preparação Concluída

O projeto foi analisado e está pronto para inicialização. Foram criados/atualizados os seguintes arquivos:

### Arquivos Criados/Atualizados:

1. ✅ **`src/llm/Dockerfile`** - Dockerfile para o serviço LLM
2. ✅ **`src/llm/main.py`** - Script principal do serviço LLM
3. ✅ **`src/llm/requirements.txt`** - Dependências Python do serviço LLM
4. ✅ **`src/.env`** - Atualizado com configurações de OpenAI, News API e LLM
5. ✅ **`docker-compose.yaml`** - Atualizado (versão removida, compatível com Docker Compose v2)
6. ✅ **`inicializar.bat`** - Script de inicialização para Windows
7. ✅ **`inicializar.sh`** - Script de inicialização para Linux/Mac
8. ✅ **`INICIALIZAR.md`** - Guia completo de inicialização

## 📋 Pré-requisitos

- ✅ Docker Desktop instalado e rodando
- ✅ Portas 80, 443 e 3306 disponíveis
- ✅ Git instalado (opcional)

## 🚀 Inicialização Rápida

### Opção 1: Script Automatizado (Recomendado)

#### Windows:
```bash
inicializar.bat
```

#### Linux/Mac:
```bash
chmod +x inicializar.sh
./inicializar.sh
```

### Opção 2: Manual

#### 1. Parar containers existentes
```bash
docker-compose down
```

#### 2. Construir e iniciar containers
```bash
docker-compose up --build -d
```

#### 3. Aguardar banco de dados (30 segundos)
```bash
# Windows PowerShell
Start-Sleep -Seconds 30

# Linux/Mac
sleep 30
```

#### 4. Acessar o container
```bash
docker exec -it laravel_app bash
```

#### 5. Dentro do container - Instalar dependências
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 6. Sair do container
```bash
exit
```

## 🔧 Configuração Adicional

### 1. Configurar API Keys no `.env`

Edite o arquivo `src/.env` e configure:

```env
# OpenAI API Key (para Agente Júlia)
OPENAI_API_KEY=sk-sua-chave-aqui

# News API Key (para Agente Pedro)
NEWS_API_KEY=sua-chave-aqui

# Email (para Agente PublishNotify)
MAIL_FROM_ADDRESS=seu-email@example.com
```

### 2. Verificar Serviços

```bash
# Verificar status dos containers
docker-compose ps

# Ver logs
docker-compose logs app
docker-compose logs db
docker-compose logs llm
```

## 🧪 Testar a Aplicação

### 1. Verificar Status da API
```bash
curl http://localhost/api/agents/status
```

### 2. Listar Ações Monitoradas
```bash
curl http://localhost/api/stock-symbols
```

### 3. Listar Artigos
```bash
curl http://localhost/api/articles
```

### 4. Executar Agentes

#### Via API:
```bash
# Agente Júlia (coleta dados financeiros)
curl -X POST http://localhost/api/agents/julia

# Agente Pedro (análise de sentimento)
curl -X POST http://localhost/api/agents/pedro

# Agente Key (gera artigos)
curl -X POST http://localhost/api/agents/key
```

#### Via Artisan (dentro do container):
```bash
docker exec -it laravel_app bash
php artisan agent:julia:fetch --symbol=PETR4
php artisan agent:pedro:analyze
php artisan agent:key:compose
```

## 📊 Estrutura do Projeto

```
residencia_2025_02_API_COM_LARAVEL/
├── src/                          # Aplicação Laravel
│   ├── app/
│   │   ├── Console/Commands/     # Comandos dos agentes
│   │   ├── Http/Controllers/     # Controladores da API
│   │   ├── Models/               # Modelos do banco de dados
│   │   ├── Services/             # Serviços (OpenAI, News, LLM)
│   │   └── Policies/             # Políticas de autorização
│   ├── database/
│   │   ├── migrations/           # Migrações do banco
│   │   └── seeders/              # Seeders de dados
│   ├── llm/                      # Serviço LLM (Python)
│   │   ├── scripts/              # Scripts Python
│   │   ├── utils/                # Utilitários Python
│   │   └── Dockerfile            # Dockerfile do serviço LLM
│   └── routes/
│       └── api.php               # Rotas da API
├── DockerConfig/                 # Configurações do Docker
├── docker-compose.yaml           # Configuração dos containers
├── Dockerfile                    # Dockerfile da aplicação
├── inicializar.bat               # Script de inicialização (Windows)
├── inicializar.sh                # Script de inicialização (Linux/Mac)
└── README.MD                     # Documentação principal
```

## 🎯 Agentes de IA

### 1. Agente Júlia
- **Função**: Coleta dados financeiros via OpenAI API
- **Comando**: `agent:julia:fetch`
- **Agendamento**: A cada 10 minutos
- **Log**: `storage/logs/agent_julia.log`

### 2. Agente Pedro
- **Função**: Analisa sentimento de mercado e mídia
- **Comando**: `agent:pedro:analyze`
- **Agendamento**: A cada hora
- **Log**: `storage/logs/agent_pedro.log`

### 3. Agente Key
- **Função**: Gera artigos financeiros usando LLM
- **Comando**: `agent:key:compose`
- **Agendamento**: A cada 30 minutos
- **Log**: `storage/logs/agent_key.log`

### 4. Agente PublishNotify
- **Função**: Notifica revisores sobre artigos pendentes
- **Comando**: `agent:publish:notify`
- **Agendamento**: A cada 15 minutos
- **Log**: `storage/logs/agent_notify.log`

### 5. Agente Cleanup
- **Função**: Limpeza e manutenção do sistema
- **Comando**: `agent:cleanup`
- **Agendamento**: Diário às 03:00
- **Log**: `storage/logs/agent_cleanup.log`

## 🔍 Verificar Logs

```bash
# Logs da aplicação
docker-compose logs -f app

# Logs dos agentes (dentro do container)
docker exec -it laravel_app bash
tail -f storage/logs/agent_julia.log
tail -f storage/logs/agent_pedro.log
tail -f storage/logs/agent_key.log
```

## 🐛 Troubleshooting

### Docker não está rodando
```bash
# Iniciar Docker Desktop e aguardar inicialização completa
```

### Porta já em uso
```bash
# Parar containers
docker-compose down

# Verificar processos usando as portas
netstat -ano | findstr :80
netstat -ano | findstr :3306
```

### Erro de permissões
```bash
# Dentro do container
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Erro de banco de dados
```bash
# Verificar se o container do banco está rodando
docker-compose ps db

# Ver logs do banco
docker-compose logs db

# Aguardar mais tempo para inicialização
sleep 60
```

### Erro de dependências
```bash
# Dentro do container
composer install --no-interaction
composer dump-autoload
```

## ✅ Checklist Final

- [ ] Docker Desktop instalado e rodando
- [ ] Containers iniciados com sucesso
- [ ] Dependências do Composer instaladas
- [ ] Migrações executadas
- [ ] Seeders executados
- [ ] Permissões configuradas
- [ ] API respondendo corretamente
- [ ] API Keys configuradas no `.env`
- [ ] Agentes podem ser executados
- [ ] Logs funcionando corretamente

## 📚 Documentação Adicional

- **`README.MD`** - Documentação principal do projeto
- **`INICIALIZAR.md`** - Guia detalhado de inicialização
- **`src/GUIA_EXECUCAO.md`** - Guia de execução completo
- **`src/EXECUTAR_TESTES.md`** - Guia de testes

## 🎉 Pronto!

O projeto está pronto para uso. Execute os scripts de inicialização ou siga os passos manuais acima.

**Boa sorte com o projeto! 🚀**

