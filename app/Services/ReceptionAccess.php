<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;

class ReceptionAccess
{
    public function lockAndAuthorize(User $actor, bool $asAdministrator = false): void
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->first();
        if ($asAdministrator) {
            $user?->administrator()->lockForUpdate()->first();
            abort_unless($user?->canAccessAdmin(), 403, 'Bạn không còn quyền tiếp nhận bệnh nhân.');

            return;
        }

        $employee = Employee::where('user_id', $actor->id)->lockForUpdate()->first();
        $employee?->detail()->lockForUpdate()->first();
        if (! $user || $user->status !== 'ACTIVE' || $user->role?->name !== 'Employee'
            || $user->role?->status !== 'ACTIVE' || $employee?->status !== 'ACTIVE') {
            abort(403, 'Bạn không còn quyền tiếp nhận bệnh nhân.');
        }
    }
}
