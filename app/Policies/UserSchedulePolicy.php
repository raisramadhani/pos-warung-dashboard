<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Schedules\UserSchedule;
use App\Models\User;

class UserSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserSchedule $userSchedule): bool
    {
        if ($user->role === RoleType::SuperAdmin) {
            return true;
        }

        return $user->merchants()->whereKey($userSchedule->merchant_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === RoleType::SuperAdmin;
    }

    public function update(User $user, UserSchedule $userSchedule): bool
    {
        return $user->role === RoleType::SuperAdmin;
    }

    public function delete(User $user, UserSchedule $userSchedule): bool
    {
        return $user->role === RoleType::SuperAdmin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->role === RoleType::SuperAdmin;
    }

    public function restore(User $user, UserSchedule $userSchedule): bool
    {
        return $user->role === RoleType::SuperAdmin;
    }

    public function forceDelete(User $user, UserSchedule $userSchedule): bool
    {
        return $user->role === RoleType::SuperAdmin;
    }
}
