<?php

namespace App\Models;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ClinicalDocument
{
    public const RESULT_FILES = ['result_pdf' => 'result_pdf_path', 'result_image' => 'result_image_path'];

    /** @var list<string> */
    private array $uploadedPaths = [];

    /** @return list<string> */
    public static function pdfRules(): array
    {
        return ['bail', 'nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:5120'];
    }

    /** @return list<string> */
    public static function imageRules(): array
    {
        return ['bail', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'];
    }

    /** Roll back only files uploaded by this operation when its database writes fail. */
    public static function transaction(Closure $operation): mixed
    {
        $documents = new self;
        try {
            return DB::transaction(fn (): mixed => $operation($documents));
        } catch (Throwable $exception) {
            try {
                Storage::disk('local')->delete($documents->uploadedPaths);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
            throw $exception;
        }
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    public function store(Request $request, string $directory, array $fields): array
    {
        if (! preg_match('~\A(?:service-results|prescriptions)/[1-9][0-9]*\z~D', $directory)) {
            throw new \InvalidArgumentException('Thư mục tài liệu y tế không hợp lệ.');
        }
        $paths = [];
        foreach ($fields as $field => $column) {
            if (! $request->hasFile($field)) {
                continue;
            }
            $path = $request->file($field)->store($directory, 'local');
            if (! $path) {
                throw ValidationException::withMessages([$field => 'Không thể lưu tệp. Vui lòng thử lại.']);
            }
            $this->uploadedPaths[] = $path;
            if (! $this->exists($path, $directory, str_ends_with($column, 'pdf_path') ? 'pdf' : 'image')) {
                throw ValidationException::withMessages([$field => 'Tệp lưu trữ không hợp lệ. Vui lòng chọn lại tệp.']);
            }
            $paths[$column] = $path;
        }

        return $paths;
    }

    public function exists(?string $path, string $directory, string $type): bool
    {
        if (! $path || ! preg_match('~\A(?:service-results|prescriptions)/[1-9][0-9]*\z~D', $directory)
            || ! str_starts_with($path, $directory.'/')) {
            return false;
        }
        $filename = substr($path, strlen($directory) + 1);
        $extensions = $type === 'pdf' ? 'pdf' : 'jpg|jpeg|png|webp';
        if (! in_array($type, ['pdf', 'image'], true)
            || ! preg_match('~\A[a-zA-Z0-9_-]+\.(?:'.$extensions.')\z~Di', $filename)) {
            return false;
        }
        $disk = Storage::disk('local');
        $root = realpath($disk->path(''));
        $resolved = realpath($disk->path($path));
        $expectedPath = $root ? $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path) : null;
        if (! $root || ! $resolved || $resolved !== $expectedPath
            || ! is_file($resolved) || ! is_readable($resolved) || ! $disk->exists($path) || $disk->size($path) < 1) {
            return false;
        }

        return in_array($disk->mimeType($path), $type === 'pdf'
            ? ['application/pdf'] : ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    public function response(?string $path, string $directory, string $type): StreamedResponse
    {
        abort_unless($this->exists($path, $directory, $type), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Cache-Control' => 'private, no-store, no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ], 'inline');
    }
}
