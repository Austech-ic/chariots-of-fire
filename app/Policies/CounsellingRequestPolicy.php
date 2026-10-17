<?php

namespace App\Policies;

use App\Models\CounsellingRequest;
use App\Models\User;

/**
 * Confidential: coordinators see all requests; counsellors only those
 * assigned to them. Nobody else — including general administrators.
 */
class CounsellingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('counselling.coordinate') || $user->hasPermission('counselling.counsel');
    }

    public function view(User $user, CounsellingRequest $request): bool
    {
        if ($user->hasPermission('counselling.coordinate')) {
            return true;
        }

        return $user->hasPermission('counselling.counsel')
            && $request->assigned_to !== null
            && $request->assigned_to === $user->counsellorProfile?->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('counselling.coordinate');
    }

    public function update(User $user, CounsellingRequest $request): bool
    {
        return $this->view($user, $request);
    }

    public function assign(User $user): bool
    {
        return $user->hasPermission('counselling.coordinate');
    }

    public function delete(User $user, CounsellingRequest $request): bool
    {
        return false; // removed only by the retention schedule
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
