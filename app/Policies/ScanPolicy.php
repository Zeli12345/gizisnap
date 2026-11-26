<?php

namespace App\Policies;

use App\Models\Scan;
use App\Models\User;

/**
 * Data ini hanya boleh dibuka dan diubah pemiliknya.
 */
class ScanPolicy
{
    public function view(User $user, Scan $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function update(User $user, Scan $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function delete(User $user, Scan $model): bool
    {
        return $model->user_id === $user->id;
    }
}
