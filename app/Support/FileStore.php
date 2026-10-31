<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persistent file storage in the database.
 *
 * A stored reference looks like "db:42". Older records still hold a plain file name
 * that lives under public/uploads/<dir>; those keep working while the file exists on disk.
 */
class FileStore
{
    public const LEGACY_DIRS = ['cv' => 'uploads/cv', 'photo' => 'uploads/photos', 'voucher' => 'uploads/vouchers'];

    public static function put(UploadedFile $file, string $kind, ?int $userId = null): string
    {
        if (!$file->isValid()) {
            throw new FileStoreException('تعذر استلام الملف، قد يكون حجمه كبيراً جداً. جرّب ملفاً أصغر من 10 ميجابايت.');
        }

        // If the deploy happened but /run-migrate was not opened yet, create the table now.
        if (!Schema::hasTable('stored_files')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                \Log::error('FileStore auto-migrate failed: ' . $e->getMessage());
            }
            if (!Schema::hasTable('stored_files')) {
                throw new FileStoreException('قاعدة البيانات تحتاج تحديث. افتح صفحة /run-migrate بحساب المدير ثم أعد المحاولة.');
            }
        }

        try {
            return self::insert($file, $kind, $userId);
        } catch (\Throwable $e) {
            \Log::error('FileStore::put failed: ' . $e->getMessage());
            throw new FileStoreException('تعذر حفظ الملف. حاول مرة أخرى، وإذا تكررت المشكلة جرّب ملفاً أصغر.');
        }
    }

    private static function insert(UploadedFile $file, string $kind, ?int $userId): string
    {
        $id = DB::table('stored_files')->insertGetId([
            'user_id'       => $userId,
            'kind'          => $kind,
            'original_name' => mb_substr($file->getClientOriginalName() ?: ($kind . '.bin'), 0, 250),
            'mime'          => $file->getMimeType() ?: 'application/octet-stream',
            'size'          => (int) $file->getSize(),
            'content'       => file_get_contents($file->getRealPath()),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return 'db:' . $id;
    }

    public static function isDb(?string $ref): bool
    {
        return is_string($ref) && str_starts_with($ref, 'db:');
    }

    public static function exists(?string $ref, string $kind): bool
    {
        if (!$ref) {
            return false;
        }
        if (self::isDb($ref)) {
            return self::tableReady() && DB::table('stored_files')->where('id', (int) substr($ref, 3))->exists();
        }
        return is_file(self::legacyPath($ref, $kind));
    }

    /**
     * Build an HTTP response for the file, or null when it no longer exists.
     */
    public static function response(?string $ref, string $kind, string $downloadName, bool $inline): ?Response
    {
        if (!$ref) {
            return null;
        }

        $disposition = $inline ? 'inline' : 'attachment';
        $safeName    = str_replace(['"', '/', '\\'], '', $downloadName);

        if (self::isDb($ref)) {
            if (!self::tableReady()) {
                return null;
            }
            $row = DB::table('stored_files')->where('id', (int) substr($ref, 3))->first();
            if (!$row) {
                return null;
            }
            return response($row->content, 200, [
                'Content-Type'           => $row->mime,
                'Content-Length'         => strlen($row->content),
                'Content-Disposition'    => $disposition . "; filename=\"file\"; filename*=UTF-8''" . rawurlencode($safeName),
                'Cache-Control'          => 'private, max-age=600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $path = self::legacyPath($ref, $kind);
        if (!is_file($path)) {
            return null;
        }
        return $inline
            ? response()->file($path, ['Cache-Control' => 'private, max-age=600'])
            : response()->download($path, $safeName);
    }

    public static function delete(?string $ref, string $kind): void
    {
        if (!$ref) {
            return;
        }
        if (self::isDb($ref)) {
            if (self::tableReady()) {
                DB::table('stored_files')->where('id', (int) substr($ref, 3))->delete();
            }
            return;
        }
        $path = self::legacyPath($ref, $kind);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /** Extension of the stored file, used to build a sensible download name. */
    public static function extension(?string $ref, string $fallback): string
    {
        if (self::isDb($ref) && self::tableReady()) {
            $name = DB::table('stored_files')->where('id', (int) substr($ref, 3))->value('original_name');
            $ext  = $name ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : '';
            return $ext ?: $fallback;
        }
        $ext = strtolower(pathinfo((string) $ref, PATHINFO_EXTENSION));
        return $ext ?: $fallback;
    }

    private static function legacyPath(string $ref, string $kind): string
    {
        return public_path((self::LEGACY_DIRS[$kind] ?? 'uploads') . '/' . basename($ref));
    }

    private static function tableReady(): bool
    {
        static $ready = false;
        return $ready = $ready ?: Schema::hasTable('stored_files');
    }
}
