<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model User - Usuários do Sistema
 * 
 * FLUXO DOS AGENTES:
 * Usuários atuam como revisores humanos no fluxo de publicação.
 * 
 * Fluxo de Revisão:
 * 1. Agente Key gera matéria → status: 'pendente_revisao'
 * 2. Agente PublishNotify envia notificação ao editor
 * 3. Usuário (editor) revisa a matéria
 * 4. Usuário aprova ou reprova:
 *    - Aprovação → status: 'aprovado' → pode ser publicado
 *    - Reprovação → status: 'reprovado' (com motivo_reprovacao)
 * 5. Se aprovado → status: 'publicado'
 * 
 * Papel do Usuário:
 * - Revisão humana obrigatória antes da publicação (fator humano)
 * - Aprovação/reprovação de artigos gerados pelo Agente Key
 * - Auditoria de revisões (reviewed_at, reviewed_by)
 * 
 * Relacionamentos:
 * - reviewedArticles: Artigos revisados pelo usuário
 * - pendingArticles: Artigos pendentes de revisão (scope)
 * - approvedArticles: Artigos aprovados pelo usuário
 * - rejectedArticles: Artigos reprovados pelo usuário
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
    ];

    /**
     * Mutator para hash da senha (Laravel 8 não suporta cast 'hashed')
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = \Hash::make($value);
    }

    /**
     * Relacionamento com artigos revisados pelo usuário
     * 
     * Artigos gerados pelo Agente Key e revisados por este usuário.
     * 
     * @return HasMany
     */
    public function reviewedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by');
    }

    /**
     * Artigos pendentes de revisão (todos, não apenas do usuário)
     * 
     * Artigos gerados pelo Agente Key aguardando revisão humana.
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
     * Artigos que este usuário aprovou após revisão.
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
     * Artigos que este usuário reprovou após revisão (com motivo_reprovacao).
     * 
     * @return HasMany
     */
    public function rejectedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by')
            ->where('status', 'reprovado');
    }

    /**
     * Artigos publicados revisados pelo usuário
     * 
     * Artigos que este usuário aprovou e foram publicados.
     * 
     * @return HasMany
     */
    public function publishedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'reviewed_by')
            ->where('status', 'publicado');
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
     * Conta artigos pendentes de revisão (todos, não apenas do usuário)
     * 
     * Útil para dashboards e notificações.
     * 
     * @return int
     */
    public function getPendingArticlesCountAttribute(): int
    {
        return Article::pendingReview()->count();
    }

    /**
     * Taxa de aprovação do usuário (0-100%)
     * 
     * Calcula a porcentagem de artigos aprovados em relação ao total revisado.
     * 
     * @return float
     */
    public function getApprovalRateAttribute(): float
    {
        $total = $this->reviewedArticles()->count();
        if ($total === 0) {
            return 0.0;
        }
        
        $approved = $this->approvedArticles()->count();
        return round(($approved / $total) * 100, 2);
    }

    /**
     * Taxa de reprovação do usuário (0-100%)
     * 
     * Calcula a porcentagem de artigos reprovados em relação ao total revisado.
     * 
     * @return float
     */
    public function getRejectionRateAttribute(): float
    {
        $total = $this->reviewedArticles()->count();
        if ($total === 0) {
            return 0.0;
        }
        
        $rejected = $this->rejectedArticles()->count();
        return round(($rejected / $total) * 100, 2);
    }
}
