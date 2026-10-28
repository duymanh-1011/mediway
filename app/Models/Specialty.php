<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialty extends Model
{
    protected $fillable = [
        'specialty_code',
        'name',
        'description',
        'image_path',
        'pre_visit_instructions',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return ['is_general' => 'boolean'];
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function scopeForDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->whereExists(function ($doctors) use ($doctorId) {
            $doctors->selectRaw('1')->from('doctors')->where('doctors.id', $doctorId)
                ->where(function ($match) {
                    $match->whereColumn('doctors.specialty_id', 'specialties.id')
                        ->orWhereExists(fn ($general) => $general->selectRaw('1')->from('specialties as assigned_specialty')
                            ->whereColumn('assigned_specialty.id', 'doctors.specialty_id')->where('assigned_specialty.is_general', true));
                });
        });
    }
}
