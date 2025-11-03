<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aqui você pode registrar todos os canais de broadcast de eventos que
| sua aplicação suporta. Os callbacks de autorização de canal fornecidos
| são usados para verificar se um usuário autenticado pode ouvir o canal.
|
| Sistema de Agentes de IA:
| Os agentes podem disparar eventos de broadcast para notificações em
| tempo real sobre:
| - Coleta de dados financeiros (Agente Júlia)
| - Análise de sentimento (Agente Pedro)
| - Geração de artigos (Agente Key)
| - Notificações de revisão (Agente PublishNotify)
| - Limpeza e manutenção (Agente Cleanup)
|
*/

/*
|--------------------------------------------------------------------------
| Canais de Usuário
|--------------------------------------------------------------------------
*/

// Canal de usuário - permite que usuários ouçam seus próprios eventos
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Canais dos Agentes de IA (Futuro)
|--------------------------------------------------------------------------
*/

// Exemplo de canais para agentes (descomente quando necessário):
// Broadcast::channel('agents.julia', function ($user) {
//     return $user->isReviewer();
// });
//
// Broadcast::channel('agents.pedro', function ($user) {
//     return $user->isReviewer();
// });
//
// Broadcast::channel('agents.key', function ($user) {
//     return $user->isReviewer();
// });
