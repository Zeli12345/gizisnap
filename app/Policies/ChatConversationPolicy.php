<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

/**
 * Data ini hanya boleh dibuka dan diubah pemiliknya.
 */
class ChatConversationPolicy
{
    public function view(User $user, ChatConversation $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function update(User $user, ChatConversation $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function delete(User $user, ChatConversation $model): bool
    {
        return $model->user_id === $user->id;
    }
}
