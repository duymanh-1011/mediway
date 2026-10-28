<?php

namespace App\Observers;

use App\Models\AdminDisplay;
use App\Models\AuditLog;
use App\Models\MedicalVisit;
use App\Models\ServiceOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** Clinical model events run inside the caller's transaction, including rollbacks. */
class ClinicalAuditObserver
{
    public function created(Model $record): void
    {
        $this->record($record, 'CREATE', [], $record->getAttributes());
    }

    public function updated(Model $record): void
    {
        // Execution and cancellation already have dedicated item audit records.
        if ($record instanceof ServiceOrderItem) {
            return;
        }
        $changes = array_diff_key($record->getChanges(), array_flip(['created_at', 'updated_at']));
        if ($changes === []) {
            return;
        }
        $before = array_intersect_key($record->getRawOriginal(), $changes);
        $action = collect(array_keys($changes))->contains(fn ($field) => $field === 'status' || str_ends_with($field, '_status_id'))
            ? 'STATUS_CHANGE' : 'UPDATE';
        $this->record($record, $action, $before, $changes);
    }

    private function record(Model $record, string $action, array $before, array $after): void
    {
        $request = request();
        if (! Auth::check() || ! $request->routeIs('admin.*') || $request->isMethodSafe()) {
            return;
        }
        $sanitize = fn (array $values): array => array_filter($values,
            fn ($key) => ! preg_match('/password|token|secret|otp|_path$/i', (string) $key), ARRAY_FILTER_USE_KEY);
        $code = $record->getAttribute('visit_code') ?? $record->getAttribute('service_order_code');
        if (! $code && $record->getAttribute('medical_visit_id')) {
            $code = MedicalVisit::whereKey($record->getAttribute('medical_visit_id'))->value('visit_code');
        }
        AuditLog::create([
            'user_id' => Auth::id(), 'role_name_snapshot' => Auth::user()->role?->name,
            'action' => $action, 'entity_type' => $record->getTable(), 'entity_id' => $record->getKey(),
            'entity_business_code' => $code, 'old_values' => $sanitize($before), 'new_values' => $sanitize($after),
            'description' => AdminDisplay::label($action).' '.mb_strtolower(AdminDisplay::label($record->getTable()))
                .($code ? ' của hồ sơ '.$code : '').'.',
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'performed_at' => now(),
        ]);
    }
}
