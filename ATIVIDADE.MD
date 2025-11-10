Lista de Exercícios (passo a passo)

1) Preparação do projeto
	1.	Criar um novo projeto (ou usar um já existente).
	2.	Configurar a conexão com banco local.
	3.	Criar banco e rodar migrações iniciais.

⸻

2) Modelagem e Migration
	1.	Criar migration para a tabela conteudos com os campos:
	•	id
	•	papel (string)
	•	conteudo (text)
	•	status (string: escrito, aprovado, reprovado)
	•	motivo_reprovacao (nullable)
	•	created_at / updated_at
	2.	Rodar a migration e verificar se a tabela foi criada.

⸻

3) Model
	1.	Criar o model Conteudo.
	2.	Definir $fillable com: papel, conteudo, status, motivo_reprovacao.

⸻


4) Regras de negócio (estado)
	1.	Status inicial sempre escrito.
	2.	Aprovar só muda escrito → aprovado.
	3.	Reprovar só muda escrito → reprovado (exigir motivo).
	4.	Editar um conteúdo reprovado deve voltar para escrito (ao salvar).

⸻

5) Rotas (API)

Criar rotas REST (em routes/api.php):
	•	POST /conteudos → criar
	•	GET /conteudos → listar
	•	GET /conteudos/{id} → visualizar
	•	POST /conteudos/{id}/aprovar → aprovar
	•	POST /conteudos/{id}/reprovar → reprovar
	•	DELETE /conteudos/{id} → deletar

⸻

6) Controller (ações mínimas)

Implementar métodos:
	•	create: cria o conteúdo com status = escrito (pode simular integração com IA).
	•	list: lista os conteúdos.
	•	show: retorna por id (404 se não existir).
	•	aprovar: valida estado atual, muda para aprovado.
	•	reprovar: exige motivo, muda para reprovado.
	•	destroy: apaga registro (204).

Entrega: respostas HTTP corretas (201, 200, 204, 400/422 quando inválido).

⸻

7) Validações
	•	papel: obrigatório, string.
	•	conteudo: obrigatório (text).
	•	reprovar: campo motivo_reprovacao é obrigatório.

⸻

*********************************************************EXTRAS*************************
8) Filtros e paginação (extra)
	•	GET /conteudos?status=escrito&papel=marketing&page=1&per_page=10
	•	Retornar data + meta (página, total, por página).

⸻

9) Auditoria (extra)
	•	Criar tabela conteudo_logs para registrar: ação, usuário (se houver), data/hora.
	•	Logar em: criar, aprovar, reprovar, deletar.

Entrega: ao aprovar/reprovar, novo registro na conteudo_logs.

⸻

10) Testes rápidos (opcional)
	•	Testes de feature para: criar, aprovar, reprovar, deletar.
	•	Testar transições inválidas (ex.: aprovar algo já aprovado).

Entrega: php artisan test verde.

⸻

11) Postman/Insomnia (opcional)
	•	Criar coleção com as 5 rotas.
	•	Incluir exemplos de body para cada ação.