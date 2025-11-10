#!/bin/bash

# Script de inicialização do projeto - Linux/Mac
echo "========================================"
echo "Inicializando Projeto Laravel com Agentes de IA"
echo "========================================"
echo ""

# Verificar se Docker está rodando
if ! docker ps > /dev/null 2>&1; then
    echo "[ERRO] Docker não está rodando!"
    echo "Por favor, inicie o Docker e tente novamente."
    exit 1
fi

echo "[OK] Docker está rodando"
echo ""

# Parar containers existentes
echo "[1/8] Parando containers existentes..."
docker-compose down
echo ""

# Construir e iniciar containers
echo "[2/8] Construindo e iniciando containers..."
docker-compose up --build -d
if [ $? -ne 0 ]; then
    echo "[ERRO] Falha ao iniciar containers!"
    exit 1
fi
echo ""

# Aguardar banco de dados inicializar
echo "[3/8] Aguardando banco de dados inicializar (30 segundos)..."
sleep 30
echo ""

# Verificar status dos containers
echo "[4/8] Verificando status dos containers..."
docker-compose ps
echo ""

# Instalar dependências do Composer
echo "[5/8] Instalando dependências do Composer..."
docker-compose exec -T app composer install --no-interaction
if [ $? -ne 0 ]; then
    echo "[ERRO] Falha ao instalar dependências!"
    exit 1
fi
echo ""

# Gerar chave da aplicação
echo "[6/8] Gerando chave da aplicação..."
docker-compose exec -T app php artisan key:generate
echo ""

# Executar migrações
echo "[7/8] Executando migrações..."
docker-compose exec -T app php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "[ERRO] Falha ao executar migrações!"
    exit 1
fi
echo ""

# Executar seeders
echo "[8/8] Executando seeders..."
docker-compose exec -T app php artisan db:seed --force
if [ $? -ne 0 ]; then
    echo "[ERRO] Falha ao executar seeders!"
    exit 1
fi
echo ""

# Configurar permissões
echo "[EXTRA] Configurando permissões..."
docker-compose exec -T app chmod -R 775 storage bootstrap/cache
docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache
echo ""

echo "========================================"
echo "Inicialização concluída com sucesso!"
echo "========================================"
echo ""
echo "Aplicação disponível em: http://localhost"
echo "API disponível em: http://localhost/api"
echo ""
echo "Para verificar o status dos agentes:"
echo "  curl http://localhost/api/agents/status"
echo ""
echo "Para executar os agentes:"
echo "  curl -X POST http://localhost/api/agents/julia"
echo "  curl -X POST http://localhost/api/agents/pedro"
echo "  curl -X POST http://localhost/api/agents/key"
echo ""

