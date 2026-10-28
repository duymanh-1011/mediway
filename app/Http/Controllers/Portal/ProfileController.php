<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $patient = $user->patient->load('detail');

        return response()->view('portal.profile.edit', compact('patient'))->header('Cache-Control', 'private, no-store');
    }

    public function update(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $patientId = $request->user()->patient->id;
        DB::transaction(function () use ($data, $patientId, $request) {
            $patient = \App\Models\Patient::whereKey($patientId)->where('user_id', $request->user()->id)
                ->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            if (array_key_exists('full_name', $data)) {
                $patient->update(['full_name' => $data['full_name']]);
            }
            $details = Arr::except($data, ['full_name', 'date_of_birth', 'gender', 'citizen_id_number']);
            if ($details !== []) {
                $patient->detail()->updateOrCreate([], $details);
            }
        });

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
