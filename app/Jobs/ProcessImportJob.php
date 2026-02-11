<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Import $import) {}

    public function handle(ImportService $importService): void
    {
        $importService->markRunning($this->import);

        try {
            $file = $this->import->inputFile;
            if (! $file) {
                $importService->markFailed($this->import, 'No input file provided.');

                return;
            }

            $contents = Storage::disk($file->disk)->get($file->path);
            if (! $contents) {
                $importService->markFailed($this->import, 'Could not read input file.');

                return;
            }

            $rows = array_filter(explode("\n", $contents));
            $totalRows = count($rows) - 1; // Exclude header

            $this->import->update(['total_rows' => max(0, $totalRows)]);

            $processedRows = 0;
            $errorCount = 0;

            foreach (array_slice($rows, 1) as $row) {
                try {
                    // Process each row — this is a stub that subclasses can override
                    $this->processRow($row);
                    $processedRows++;
                } catch (\Throwable $e) {
                    $errorCount++;
                    Log::warning("Import row error: {$e->getMessage()}", [
                        'import_id' => $this->import->id,
                        'row' => $processedRows + $errorCount,
                    ]);
                }

                $importService->updateProgress($this->import, $processedRows, $errorCount);
            }

            $importService->markCompleted($this->import);
        } catch (\Throwable $e) {
            $importService->markFailed($this->import, $e->getMessage());
        }
    }

    /**
     * Process a single CSV row. Override in subclasses for specific import types.
     */
    protected function processRow(string $row): void
    {
        // Default: no-op. Subclasses implement specific logic.
    }
}
