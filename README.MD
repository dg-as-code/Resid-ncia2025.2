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
