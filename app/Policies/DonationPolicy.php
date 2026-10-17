<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

/** Financial records are never created, edited or deleted by hand. */
class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('donations.view');
    }

    public function view(User $user, Donation $donation): bool
    {
        return $user->hasPermission('donations.view');
    }

    public function refund(User $user, Donation $donation): bool
    {
        return $user->hasPermission('donations.refund');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('donations.export');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Donation $donation): bool
    {
        return false;
    }

    public function delete(User $user, Donation $donation): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
