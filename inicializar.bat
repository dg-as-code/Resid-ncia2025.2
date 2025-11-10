@echo off
REM Script de inicialização do projeto - Windows
echo ========================================
echo Inicializando Projeto Laravel com Agentes de IA
echo ========================================
echo.

REM Verificar se Docker está rodando
docker ps >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Docker nao esta rodando!
    echo Por favor, inicie o Docker Desktop e tente novamente.
    pause
    exit /b 1
)

echo [OK] Docker esta rodando
echo.

REM Parar containers existentes
echo [1/8] Parando containers existentes...
docker-compose down
echo.

REM Construir e iniciar containers
echo [2/8] Construindo e iniciando containers...
docker-compose up --build -d
if errorlevel 1 (
    echo [ERRO] Falha ao iniciar containers!
    pause
    exit /b 1
)
echo.

REM Aguardar banco de dados inicializar
echo [3/8] Aguardando banco de dados inicializar (30 segundos)...
timeout /t 30 /nobreak >nul
echo.

REM Verificar status dos containers
echo [4/8] Verificando status dos containers...
docker-compose ps
echo.

REM Instalar dependências do Composer
echo [5/8] Instalando dependencias do Composer...
docker-compose exec -T app composer install --no-interaction
if errorlevel 1 (
    echo [ERRO] Falha ao instalar dependencias!
    pause
    exit /b 1
)
echo.

REM Gerar chave da aplicação
echo [6/8] Gerando chave da aplicacao...
docker-compose exec -T app php artisan key:generate
echo.

REM Executar migrações
echo [7/8] Executando migracoes...
docker-compose exec -T app php artisan migrate --force
if errorlevel 1 (
    echo [ERRO] Falha ao executar migracoes!
    pause
    exit /b 1
)
echo.

REM Executar seeders
echo [8/8] Executando seeders...
docker-compose exec -T app php artisan db:seed --force
if errorlevel 1 (
    echo [ERRO] Falha ao executar seeders!
    pause
    exit /b 1
)
echo.

REM Configurar permissões
echo [EXTRA] Configurando permissoes...
docker-compose exec -T app chmod -R 775 storage bootstrap/cache
docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache
echo.

echo ========================================
echo Inicializacao concluida com sucesso!
echo ========================================
echo.
echo Aplicacao disponivel em: http://localhost
echo API disponivel em: http://localhost/api
echo.
echo Para verificar o status dos agentes:
echo   curl http://localhost/api/agents/status
echo.
echo Para executar os agentes:
echo   curl -X POST http://localhost/api/agents/julia
echo   curl -X POST http://localhost/api/agents/pedro
echo   curl -X POST http://localhost/api/agents/key
echo.
pause

