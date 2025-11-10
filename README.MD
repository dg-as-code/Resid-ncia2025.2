# Mentoria - API 2025.2

**Proposta:** Criar um grupo de agentes de inteligência artificial para analisar ações e gerar conteúdos recomendando (ou não) a compra, com base em dados reais e percepção de mercado.

- **Júlia:** coleta os dados financeiros atualizados via Yahoo Finance.  
- **Pedro:** analisa o que o mercado e a mídia estão dizendo sobre a empresa.  
- **Key:** jornalista experiente que redige o conteúdo final, com base nos dados dos outros dois.  
- **Fator humano:** revisa e aprova o conteúdo antes da publicação.  

O objetivo é produzir matérias financeiras claras, confiáveis e baseadas em dados, com apoio de IA e curadoria humana.

Tecnologias que iremos utilizar:  
- Inteligência Artificial (assistentes virtuais, processamento de linguagem natural, modelos de IA, etc.)  
- HTML, CSS, JavaScript  
- Laravel + PHP  
- Python  

Recomendação: já comecem a estudar algumas dessas tecnologias, para que na próxima semana estejam mais familiarizados com o conteúdo.  

---

## Diretórios

- **src/** → Aplicação WordPress  
- **db/** → Arquivos do banco de dados (**local!!! Não subir para o Git**)  
- **DockerConfig/** → Arquivos de configuração do Docker  

---

## 🚀 Como Executar o Projeto

### ⚡ Inicialização Rápida

#### Opção 1: Script Automatizado (Recomendado)

**Windows:**
```bash
inicializar.bat
```

**Linux/Mac:**
```bash
chmod +x inicializar.sh
./inicializar.sh
```

#### Opção 2: Manual (Docker)

```bash
# 1. Na raiz do projeto, levantar o ambiente
docker-compose up --build -d

# 2. Aguardar banco de dados inicializar (30 segundos)
# Windows PowerShell: Start-Sleep -Seconds 30
# Linux/Mac: sleep 30

# 3. Acessar o container da aplicação
docker exec -it laravel_app bash

# 4. Dentro do container, configurar a aplicação
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Sair do container
exit

# 6. Acessar a aplicação
# http://localhost
```

### 📖 Guias Detalhados

#### 🏗️ Arquitetura
- **[ARQUITETURA.md](ARQUITETURA.md)** - Arquitetura completa do projeto
- **[DIAGRAMAS.md](DIAGRAMAS.md)** - Diagramas visuais do sistema

#### 🚀 Execução
- **[INICIALIZACAO_COMPLETA.md](INICIALIZACAO_COMPLETA.md)** - Guia completo de inicialização
- **[INICIALIZAR.md](INICIALIZAR.md)** - Instruções passo a passo
- **[src/GUIA_EXECUCAO.md](src/GUIA_EXECUCAO.md)** - Guia de execução e uso
- **[src/EXECUTAR_TESTES.md](src/EXECUTAR_TESTES.md)** - Guia de testes

### 🎯 Execução Rápida dos Agentes

```bash
# Via Artisan (dentro do container)
php artisan agent:julia:fetch --symbol=PETR4
php artisan agent:pedro:analyze
php artisan agent:key:compose

# Via API (de fora do container)
curl -X POST http://localhost/api/agents/julia
curl -X POST http://localhost/api/agents/pedro
curl -X POST http://localhost/api/agents/key
```

### 📊 Verificar Status

```bash
# Status dos agentes
curl http://localhost/api/agents/status

# Listar ações monitoradas
curl http://localhost/api/stock-symbols

# Listar artigos gerados
curl http://localhost/api/articles
## Como rodar

### 1. Levantar o ambiente (na raiz do projeto)

```bash
docker-compose up --build -d
```

### 2. Acessando a aplicação

```bash
http://localhost
```

### 3. Acessando o container da aplicação

```bash
docker exec -it agenda bash
```

### 4. Instalando as dependências

```bash
composer install
```

### 5. Acessando o container do Banco de Dados

```bash
docker exec -it db_agenda bash
```

---

## Problemas comuns no Windows

```bash
git config core.autocrlf input
```

---

## Observação Importante

Caso você tenha problemas com o **Dockerfile**, efetue a instalação manualmente seguindo as instruções no arquivo [README2](README2.md).
