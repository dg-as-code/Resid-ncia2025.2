<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * Policy para Artigos/Matérias
 * 
 * Controla permissões de acesso e ações sobre artigos gerados pelos agentes de IA.
 * Implementa regras de negócio para revisão humana (Fator humano).
 */
class ArticlePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        // Todos os usuários autenticados podem listar artigos
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Article $article): bool
    {
        // Todos os usuários autenticados podem visualizar artigos
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        // Artigos são criados apenas pelos agentes de IA
        // Usuários não podem criar artigos manualmente
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Article $article): bool
    {
        // Apenas revisores podem editar artigos
        // Apenas artigos reprovados podem ser editados
        return $user->isReviewer() && $article->status === 'reprovado';
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Article $article): bool
    {
        // Apenas o revisor que reprovou ou admins podem deletar
        return $user->isReviewer() && 
               ($article->reviewed_by === $user->id || $article->status === 'reprovado');
    }

    /**
     * Determine whether the user can approve the article.
     * 
     * Regra de negócio: Apenas artigos com status "pendente_revisao" podem ser aprovados.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user, Article $article): Response|bool
    {
        // Apenas revisores podem aprovar
        if (!$user->isReviewer()) {
            return Response::deny('Apenas revisores podem aprovar artigos.');
        }

        // Apenas artigos pendentes podem ser aprovados
        if ($article->status !== 'pendente_revisao') {
            return Response::deny('Apenas artigos com status "pendente_revisao" podem ser aprovados.');
        }

        return true;
    }

    /**
     * Determine whether the user can reject the article.
     * 
     * Regra de negócio: Apenas artigos com status "pendente_revisao" podem ser reprovados.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function reject(User $user, Article $article): Response|bool
    {
        // Apenas revisores podem reprovar
        if (!$user->isReviewer()) {
            return Response::deny('Apenas revisores podem reprovar artigos.');
        }

        // Apenas artigos pendentes podem ser reprovados
        if ($article->status !== 'pendente_revisao') {
            return Response::deny('Apenas artigos com status "pendente_revisao" podem ser reprovados.');
        }

        return true;
    }

    /**
     * Determine whether the user can publish the article.
     * 
     * Regra de negócio: Apenas artigos aprovados podem ser publicados.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function publish(User $user, Article $article): Response|bool
    {
        // Apenas revisores podem publicar
        if (!$user->isReviewer()) {
            return Response::deny('Apenas revisores podem publicar artigos.');
        }

        // Apenas artigos aprovados podem ser publicados
        if ($article->status !== 'aprovado') {
            return Response::deny('Apenas artigos aprovados podem ser publicados.');
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Article $article): bool
    {
        // Apenas revisores podem restaurar artigos
        return $user->isReviewer();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Article $article): bool
    {
        // Apenas admins podem deletar permanentemente
        // Você pode adicionar uma verificação de role admin aqui
        return false;
    }
}

