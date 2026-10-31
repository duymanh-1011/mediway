<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessPortal(), 403);
        $patient = $user->patient;
        $patient->load('detail');
        $avatarAvailable = $this->avatarPath($patient) !== null;

        return response()->view('portal.profile.index', compact('user', 'patient', 'avatarAvailable'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function avatar(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessPortal(), 403);
        $path = $this->avatarPath($user->patient);
        abort_unless($path, 404);
        $disk = Storage::disk('local');

        return $disk->response($path, 'avatar', [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function edit(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessPortal(), 403);
        $bookingSlot = $request->validate(['booking_slot' => ['nullable', 'integer', 'exists:appointment_time_slots,id']])['booking_slot'] ?? null;
        $patient = $user->patient->load('detail');

        return response()->view('portal.profile.edit', compact('patient', 'bookingSlot'))->header('Cache-Control', 'private, no-store');
    }

    public function phoneChange(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessPortal(), 403);

        return response()->view('portal.profile.phone', compact('user'))->header('Cache-Control', 'private, no-store');
    }

    public function update(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $bookingSlot = $data['booking_slot'] ?? null;
        unset($data['booking_slot']);
        $patientId = $request->user()->patient->id;
        DB::transaction(function () use ($data, $patientId, $request) {
            $patient = \App\Models\Patient::whereKey($patientId)->where('user_id', $request->user()->id)
                ->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $patientFields = Arr::only($data, ['full_name', 'date_of_birth', 'gender']);
            foreach (['date_of_birth', 'gender'] as $field) {
                if (! empty($patientFields[$field]) && $patient->{$field} !== null && $patient->{$field} !== '') {
                    throw ValidationException::withMessages([$field => 'Thông tin này đã được cập nhật và không thể tự thay đổi.']);
                }
                if (array_key_exists($field, $patientFields) && $patientFields[$field] === null) {
                    unset($patientFields[$field]);
                }
            }
            if ($patientFields !== []) {
                $patient->update($patientFields);
            }
            $details = Arr::except($data, ['full_name', 'date_of_birth', 'gender']);
            if (array_key_exists('citizen_id_number', $details)) {
                if ($details['citizen_id_number'] === null) {
                    unset($details['citizen_id_number']);
                } elseif ($patient->detail()->lockForUpdate()->first()?->citizen_id_number) {
                    throw ValidationException::withMessages(['citizen_id_number' => 'Số CCCD đã được cập nhật và không thể tự thay đổi.']);
                }
            }
            if ($details !== []) {
                $patient->detail()->updateOrCreate([], $details);
            }
        });

        if ($bookingSlot) {
            app(\App\Support\Portal\BookingProfileGate::class)->forgetPending();
            return redirect()->route('patient.appointments.create', ['slot' => $bookingSlot])
                ->with('success', 'Thông tin hồ sơ đã được cập nhật.');
        }

        $patient = $request->user()->patient->fresh();
        if ($resume = app(\App\Support\Portal\BookingProfileGate::class)->resumeAfterProfileSaved($patient)) {
            return $resume;
        }

        return redirect()->route('patient.profile.show')->with('success', 'Thông tin hồ sơ đã được cập nhật.');
    }

    private function avatarPath(\App\Models\Patient $patient): ?string
    {
        $path = $patient->detail?->avatar_path;
        $prefix = 'patient-documents/'.$patient->id.'/';
        if (! is_string($path) || ! str_starts_with($path, $prefix)
            || str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }
        $disk = Storage::disk('local');

        return $disk->exists($path) && in_array($disk->mimeType($path), ['image/jpeg', 'image/png', 'image/webp'], true) ? $path : null;
    }
}
