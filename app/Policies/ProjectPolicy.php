<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends ModelPolicy
{
    public function update(User $user, Project $project): bool
    {
        if ($user->user_type === 'employee') {
            return $project->created_by == $user->id;
        }

        $ability = 'projects.update';
        if (in_array($ability, self::EMPLOYEE_ALLOWED_ABILITIES, true)) {
            return true;
        }

        return $user->roles->where('role_name', $ability)->isNotEmpty();
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->user_type === 'employee') {
            return $project->created_by == $user->id;
        }

        $ability = 'projects.delete';
        return $user->roles->where('role_name', $ability)->isNotEmpty();
    }
}
