<?php

namespace App\Policies;

use App\Models\Handbook;
use App\Models\HandbookPage;
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

    public function attachSharedPage(User $user, Handbook $handbook, HandbookPage $page): bool
    {
        if (! $this->ownsOrAdmin($user, $handbook)) {
            return false;
        }

        if (! $page->is_shareable) {
            return false;
        }

        if ($page->positions()->where('handbook_id', $handbook->id)->exists()) {
            return false;
        }

        return true;
    }

    private function ownsOrAdmin(User $user, Handbook $handbook): bool
    {
        return $user->isAdmin() || $handbook->user_id === $user->id;
    }
}
