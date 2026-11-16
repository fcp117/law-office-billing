<?php

namespace App\Policies;

use App\Models\LawyerRate;
use App\Models\User;

class LawyerRatePolicy
{
    /**
     * Determine whether the user can view a lawyer's billing rate history.
     */
    public function viewAny(User $authUser, ?User $targetLawyer = null): bool
    {
        return $this->isPartnerOrAdmin($authUser);
    }

    /**
     * Determine whether the user can view a specific rate record.
     */
    public function view(User $authUser, LawyerRate $lawyerRate): bool
    {
        return $this->isPartnerOrAdmin($authUser);
    }

    /**
     * Determine whether the user can create new billing rate entries.
     */
    public function create(User $authUser, ?User $targetLawyer = null): bool
    {
        return $this->isPartnerOrAdmin($authUser);
    }

    /**
     * Determine whether the user can delete a billing rate entry.
     */
    public function delete(User $authUser, LawyerRate $lawyerRate): bool
    {
        return $this->isPartnerOrAdmin($authUser);
    }

    /**
     * Check if the authenticated user holds a Partner or Admin role.
     * Adjust the column/method check below if your User model uses Spatie roles or a boolean flag.
     */
    private function isPartnerOrAdmin(User $user): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        return str_contains($role, 'partner') || $role === 'admin';
    }
}