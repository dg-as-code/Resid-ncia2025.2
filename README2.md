# Mentoria - API 2025.2

## Proposta

O projeto consiste na criação de um **grupo de agentes de inteligência artificial** para analisar ações e gerar conteúdos recomendando (ou não) a compra, com base em dados reais e percepção de mercado.

- **Júlia** → coleta dados financeiros atualizados via Yahoo Finance  
- **Pedro** → analisa o que o mercado e a mídia estão dizendo sobre a empresa  
- **Key** → jornalista experiente que redige o conteúdo final com base nos dados dos dois  
- **Fator humano** → revisa e aprova o conteúdo antes da publicação  

**Objetivo:** produzir matérias financeiras **claras, confiáveis e baseadas em dados**, com apoio de IA e curadoria humana.

---

## Tecnologias Utilizadas

- **Inteligência Artificial** (assistentes virtuais, PLN, modelos de IA, etc.)  
- **HTML, CSS, JavaScript**  
- **Laravel + PHP**  
- **Python**  

---

## Estrutura de Diretórios

- `src/` → aplicação Laravel  
- `db/` → arquivos do banco de dados (**não versionar!**)  

---

## Pré-requisitos

Antes de iniciar, instale:

- [PHP >= 8.0](https://www.php.net/downloads)  
- [Composer](https://getcomposer.org/download/)  
- [MySQL ou MariaDB](https://dev.mysql.com/downloads/)  
- [Node.js + NPM](https://nodejs.org/en/download)  
- [Laravel Installer (opcional)](https://laravel.com/docs/11.x/installation)  

---

## Como rodar o projeto

### 1. Clone o repositório

```bash
git clone <url-do-repo>
cd <nome-do-repo>
```

### 2. Instale as dependências do Laravel

```bash
composer install
```

### 3. Configure o ambiente

Edite o arquivo `.env` (dentro da pasta `src`) com os dados do seu banco:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1 # host da sua conexão (localhost ou IP)
DB_PORT=3306
DB_DATABASE=db   # nome do banco
DB_USERNAME=root
DB_PASSWORD=     # deixe em branco se não tiver senha
```

### 4. Migre o banco de dados

```bash
php artisan migrate
```

Verifique no MySQL Workbench se as tabelas foram criadas corretamente.

### 5. Instale dependências do frontend

```bash
npm install
npm run dev
```

### 6. Levante o servidor local

```bash
php artisan serve
```

Acesse em: [http://localhost:8000](http://localhost:8000)

---

## Problemas comuns no Windows

Se ocorrer erro de quebra de linha ao clonar o repositório, rode:

```bash
git config core.autocrlf input
```

---

## Observações finais

* Nunca versionar a pasta `db/` nem arquivos sensíveis.
* O arquivo `.env` já está listado no `.gitignore`.
* Para rodar os testes automatizados:

```bash
php artisan test
```