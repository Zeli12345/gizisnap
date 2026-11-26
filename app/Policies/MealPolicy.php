<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;

/**
 * Data ini hanya boleh dibuka dan diubah pemiliknya.
 */
class MealPolicy
{
    public function view(User $user, Meal $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function update(User $user, Meal $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function delete(User $user, Meal $model): bool
    {
        return $model->user_id === $user->id;
    }
}
