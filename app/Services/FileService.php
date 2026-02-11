<?php

namespace App\Services;

use App\Models\File;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileService
{
    /**
     * Upload a file to storage and create a File record.
     */
    public function upload(UploadedFile $uploadedFile, string $disk = 'local'): File
    {
        $tenantId = app(TenantContext::class)->id();
        $path = $uploadedFile->store("tenants/{$tenantId}/files", $disk);

        return File::create([
            'tenant_id' => $tenantId,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
            'uploaded_by_user_id' => auth()->id(),
        ]);
    }

    /**
     * Generate a signed temporary download URL.
     */
    public function download(File $file): string
    {
        $disk = Storage::disk($file->disk);

        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($file->path, now()->addMinutes(5));
        }

        // Local disk fallback — return the path for streaming
        return $file->path;
    }

    /**
     * Generate a presigned upload URL (stubbed for local, real for S3).
     *
     * @return array{url: string, headers: array<string, string>}
     */
    public function generateUploadUrl(string $filename, string $mimeType, string $disk = 'local'): array
    {
        $tenantId = app(TenantContext::class)->id();
        $path = "tenants/{$tenantId}/files/{$filename}";

        // For S3, generate a real presigned URL
        $storageDisk = Storage::disk($disk);
        if (method_exists($storageDisk, 'temporaryUploadUrl')) {
            $result = $storageDisk->temporaryUploadUrl($path, now()->addMinutes(15), [
                'ContentType' => $mimeType,
            ]);

            return [
                'url' => $result['url'],
                'headers' => $result['headers'] ?? [],
            ];
        }

        // Stub for local disk
        return [
            'url' => '/api/v1/files/upload',
            'headers' => [],
        ];
    }

    /**
     * Delete a file from storage and database.
     */
    public function delete(File $file): bool
    {
        Storage::disk($file->disk)->delete($file->path);

        return $file->delete();
    }
}
