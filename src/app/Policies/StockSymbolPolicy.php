<?php

namespace App\Policies;

use App\Models\StockSymbol;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para Ações (Stock Symbols)
 * 
 * Controla permissões de acesso e ações sobre ações monitoradas pelo sistema.
 */
class StockSymbolPolicy
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
        // Todos os usuários autenticados podem listar ações
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockSymbol  $stockSymbol
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StockSymbol $stockSymbol): bool
    {
        // Todos os usuários autenticados podem visualizar ações
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
        // Apenas revisores ou admins podem criar ações monitoradas
        return $user->isReviewer();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockSymbol  $stockSymbol
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StockSymbol $stockSymbol): bool
    {
        // Apenas revisores ou admins podem atualizar ações
        return $user->isReviewer();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockSymbol  $stockSymbol
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StockSymbol $stockSymbol): bool
    {
        // Apenas revisores ou admins podem remover ações
        return $user->isReviewer();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockSymbol  $stockSymbol
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StockSymbol $stockSymbol): bool
    {
        // Apenas revisores podem restaurar ações
        return $user->isReviewer();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StockSymbol  $stockSymbol
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StockSymbol $stockSymbol): bool
    {
        // Apenas admins podem deletar permanentemente
        return false;
    }
}

