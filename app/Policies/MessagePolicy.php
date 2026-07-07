<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Message;
use App\Models\User;

class MessagePolicy extends BasePolicy
{
    protected function resource(): string
    {
        return 'messages';
    }

    public function view(User $user, mixed $model): bool
    {
        if (! parent::view($user, $model)) {
            return false;
        }

        if (in_array($user->role, [UserRole::Admin, UserRole::Manager], true)) {
            return true;
        }

        return $model instanceof Message
            && ((int) $model->sender_id === (int) $user->id || (int) $model->recipient_id === (int) $user->id);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->view($user, $model);
    }
}
