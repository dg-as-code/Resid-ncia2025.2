# 🚀 Guia de Inicialização do Projeto

Este guia mostra como inicializar o projeto do zero.

## 📋 Pré-requisitos

- Docker e Docker Compose instalados
- Git instalado
- Portas 80, 443 e 3306 disponíveis

## 🔧 Passo a Passo

### 1. Parar Containers Existentes (se houver)

```bash
docker-compose down
```

### 2. Construir e Iniciar Containers

```bash
docker-compose up --build -d
```

### 3. Verificar Status dos Containers

```bash
docker-compose ps
```

### 4. Acessar o Container da Aplicação

```bash
docker exec -it laravel_app bash
```

### 5. Dentro do Container - Instalar Dependências

```bash
composer install
```

### 6. Gerar Chave da Aplicação (se necessário)

```bash
php artisan key:generate
```

### 7. Executar Migrações

```bash
php artisan migrate
```

### 8. Executar Seeders

```bash
php artisan db:seed
```

### 9. Verificar Permissões de Storage

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 10. Verificar se a Aplicação Está Funcionando

```bash
# Testar rota de status
curl http://localhost/api/agents/status

# Listar ações monitoradas
curl http://localhost/api/stock-symbols

# Listar artigos
curl http://localhost/api/articles
```

## 🎯 Executar Agentes

### Via Artisan (dentro do container)

```bash
php artisan agent:julia:fetch --symbol=PETR4
php artisan agent:pedro:analyze
php artisan agent:key:compose
```

### Via API (de fora do container)

```bash
curl -X POST http://localhost/api/agents/julia
curl -X POST http://localhost/api/agents/pedro
curl -X POST http://localhost/api/agents/key
```

## 🔍 Verificar Logs

```bash
# Logs da aplicação
docker-compose logs app

# Logs do banco de dados
docker-compose logs db

# Logs do serviço LLM
docker-compose logs llm

# Logs em tempo real
docker-compose logs -f app
```

## 🐛 Troubleshooting

### Erro: "Port already in use"

```bash
# Parar containers usando as portas
docker-compose down

# Ou alterar as portas no docker-compose.yaml
```

### Erro: "Database connection refused"

```bash
# Verificar se o container do banco está rodando
docker-compose ps db

# Verificar logs do banco
docker-compose logs db

# Aguardar alguns segundos para o banco inicializar
sleep 10
```

### Erro: "Permission denied"

```bash
# Dentro do container
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Erro: "Class not found"

```bash
# Recarregar autoloader
composer dump-autoload
```

## ✅ Checklist de Inicialização

- [ ] Containers Docker rodando
- [ ] Dependências do Composer instaladas
- [ ] Chave da aplicação gerada
- [ ] Migrações executadas
- [ ] Seeders executados
- [ ] Permissões de storage configuradas
- [ ] API respondendo corretamente
- [ ] Agentes podem ser executados

## 📊 Próximos Passos

1. Configurar API keys no `.env`:
   - `OPENAI_API_KEY` - Para o Agente Júlia
   - `NEWS_API_KEY` - Para o Agente Pedro

2. Testar os agentes:
   - Executar Agente Júlia para coletar dados
   - Executar Agente Pedro para analisar sentimento
   - Executar Agente Key para gerar artigos

3. Configurar agendamento:
   - Os agentes são agendados automaticamente via `Kernel.php`
   - Verificar logs em `storage/logs/`

---

**Projeto inicializado com sucesso! 🎉**

