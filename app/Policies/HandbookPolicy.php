<?php

namespace App\Policies;

use App\Models\Handbook;
use App\Models\User;

class HandbookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAuthor();
    }

    public function view(User $user, Handbook $handbook): bool
    {
        return $this->ownsOrAdmin($user, $handbook);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Handbook $handbook): bool
    {
        return $this->ownsOrAdmin($user, $handbook);
    }

    public function delete(User $user, Handbook $handbook): bool
    {
        return $this->ownsOrAdmin($user, $handbook);
    }

    public function assignOwner(User $user): bool
    {
        return $user->isAdmin();
    }

    private function ownsOrAdmin(User $user, Handbook $handbook): bool
    {
        return $user->isAdmin() || $handbook->user_id === $user->id;
    }
}
