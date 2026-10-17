<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('counselling.coordinate') || $user->hasPermission('counselling.counsel');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasPermission('counselling.coordinate')) {
            return true;
        }

        return $user->hasPermission('counselling.counsel') && $appointment->counsellor_id === $user->counsellorProfile?->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('counselling.coordinate');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return false; // cancel instead, so history is preserved
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
