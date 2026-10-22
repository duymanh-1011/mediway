<?php

namespace App\Providers;

use App\Models\BusinessCodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (BusinessCodeGenerator::CATALOGS as $field => [$model]) {
            $model::creating(function (Model $record) use ($field): void {
                if (! $record->getAttribute($field)) {
                    $record->setAttribute($field, app(BusinessCodeGenerator::class)->next($field));
                }
            });
            $model::updating(function (Model $record) use ($field): void {
                if ($record->isDirty($field)) {
                    throw ValidationException::withMessages([$field => 'Mã đã cấp được giữ nguyên, không thể thay đổi thủ công.']);
                }
            });
        }

        View::composer('admin.layouts.partials.business-code', function (\Illuminate\View\View $view): void {
            $data = $view->getData();
            $record = $data['record'] ?? null;
            $view->with('displayCode', $record?->getAttribute($data['field']) ?? app(BusinessCodeGenerator::class)->preview($data['field']));
        });

        Paginator::defaultView('admin.layouts.partials.pagination');
        Paginator::defaultSimpleView('admin.layouts.partials.pagination');
        Lang::addLines(
            Arr::dot(['validation' => config('admin.validation', [])]), 'vi'
        );
    }
}
