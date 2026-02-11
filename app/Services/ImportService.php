<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Support\Tenancy\TenantContext;

class ImportService
{
    /**
     * Create a new import record.
     *
     * @param  array<string, mixed>  $meta
     */
    public function create(string $type, int $totalRows = 0, ?int $inputFileId = null, array $meta = []): Import
    {
        return Import::create([
            'tenant_id' => app(TenantContext::class)->id(),
            'type' => $type,
            'status' => ImportStatus::Queued,
            'progress' => 0,
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'error_count' => 0,
            'input_file_id' => $inputFileId,
            'meta' => $meta,
            'created_by_user_id' => auth()->id(),
        ]);
    }

    /**
     * Mark an import as running.
     */
    public function markRunning(Import $import): void
    {
        $import->update([
            'status' => ImportStatus::Running,
        ]);
    }

    /**
     * Update import progress.
     */
    public function updateProgress(Import $import, int $processedRows, int $errorCount = 0): void
    {
        $progress = $import->total_rows > 0
            ? min(100, (int) round(($processedRows / $import->total_rows) * 100))
            : 0;

        $import->update([
            'processed_rows' => $processedRows,
            'error_count' => $errorCount,
            'progress' => $progress,
        ]);
    }

    /**
     * Mark an import as completed.
     */
    public function markCompleted(Import $import, ?int $resultFileId = null): void
    {
        $import->update([
            'status' => ImportStatus::Completed,
            'progress' => 100,
            'result_file_id' => $resultFileId,
        ]);
    }

    /**
     * Mark an import as failed.
     */
    public function markFailed(Import $import, ?string $errorMessage = null): void
    {
        $meta = $import->meta ?? [];
        if ($errorMessage) {
            $meta['error_message'] = $errorMessage;
        }

        $import->update([
            'status' => ImportStatus::Failed,
            'meta' => $meta,
        ]);
    }
}
