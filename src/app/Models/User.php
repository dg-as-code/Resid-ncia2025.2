<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model User - Usuários do sistema
 * 
 * Usuários podem revisar e aprovar artigos gerados pelos agentes de IA.
 * O sistema de agentes utiliza este modelo para:
 * - Revisão humana de matérias (Fator humano)
 * - Aprovação/reprovação de artigos
 * - Auditoria de revisões
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relacionamento com artigos revisados pelo usuário
     * 
     * @return HasMany
     */
    public function reviewedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by');
    }

    /**
     * Artigos pendentes de revisão pelo usuário
     * 
     * @return HasMany
     */
    public function pendingArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by')
            ->where('status', 'pendente_revisao');
    }

    /**
     * Artigos aprovados pelo usuário
     * 
     * @return HasMany
     */
    public function approvedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by')
            ->where('status', 'aprovado');
    }

    /**
     * Artigos reprovados pelo usuário
     * 
     * @return HasMany
     */
    public function rejectedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by')
            ->where('status', 'reprovado');
    }

    /**
     * Verifica se o usuário é um revisor (tem artigos revisados)
     * 
     * @return bool
     */
    public function isReviewer(): bool
    {
        return $this->reviewedArticles()->exists();
    }

    /**
     * Conta total de artigos revisados pelo usuário
     * 
     * @return int
     */
    public function getTotalReviewedArticlesAttribute(): int
    {
        return $this->reviewedArticles()->count();
    }

    /**
     * Conta artigos pendentes de revisão
     * 
     * @return int
     */
    public function getPendingArticlesCountAttribute(): int
    {
        // Retorna todos os artigos pendentes (não apenas os do usuário)
        return Article::pendingReview()->count();
    }
}
