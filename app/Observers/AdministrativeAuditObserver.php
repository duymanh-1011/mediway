<?php

namespace App\Observers;

use App\Models\AdminDisplay;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** Administrative writes and their audit event share the controller's transaction. */
class AdministrativeAuditObserver
{
    public function created(Model $record): void
    {
        $this->record($record, 'CREATE', [], $record->getAttributes());
    }

    public function updated(Model $record): void
    {
        $changes = array_diff_key($record->getChanges(), array_flip(['created_at', 'updated_at']));
        if ($changes === []) {
            return;
        }
        $before = array_intersect_key($record->getRawOriginal(), $changes);
        $action = array_key_exists('status', $changes) ? 'STATUS_CHANGE' : 'UPDATE';
        $this->record($record, $action, $before, $changes);
    }

    public function deleted(Model $record): void
    {
        $this->record($record, 'DELETE', $record->getAttributes(), []);
    }

    private function record(Model $record, string $action, array $before, array $after): void
    {
        $request = request();
        if (! Auth::check() || ! $request->routeIs('admin.*') || $request->isMethodSafe()) {
            return;
        }
        $credentialChanged = $record instanceof User && $action === 'UPDATE' && array_key_exists('password', $after);
        $code = $this->businessCode($record);
        $description = $credentialChanged ? 'Đặt lại mật khẩu tài khoản '.$code.'.'
            : AdminDisplay::label($action).' '.mb_strtolower(AdminDisplay::label($record->getTable())).($code ? ' '.$code : '').'.';
        $newValues = $this->sanitize($after);
        if ($credentialChanged) {
            $newValues['credential_changed'] = true;
        }
        AuditLog::create([
            'user_id' => Auth::id(), 'role_name_snapshot' => Auth::user()->role?->name,
            'action' => $action, 'entity_type' => $record->getTable(), 'entity_id' => $record->getKey(),
            'entity_business_code' => $code, 'old_values' => $this->sanitize($before), 'new_values' => $newValues,
            'description' => $description, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'performed_at' => now(),
        ]);
    }

    private function sanitize(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if (preg_match('/password|token|secret|otp|citizen_id_number/i', $key)) {
                continue;
            }
            if (str_ends_with($key, '_path')) {
                $out['documents_changed'] = true;
            } elseif (! in_array($key, ['created_at', 'updated_at'], true)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function businessCode(Model $record): ?string
    {
        foreach (['business_code', 'doctor_code', 'employee_code', 'patient_code'] as $field) {
            if ($record->getAttribute($field)) {
                return $record->getAttribute($field);
            }
        }
        foreach (['doctor_id' => [Doctor::class, 'doctor_code'], 'employee_id' => [Employee::class, 'employee_code'],
            'patient_id' => [Patient::class, 'patient_code'], 'user_id' => [User::class, 'business_code']] as $field => [$model, $code]) {
            if ($record->getAttribute($field)) {
                return $model::whereKey($record->getAttribute($field))->value($code);
            }
        }

        return null;
    }
}
