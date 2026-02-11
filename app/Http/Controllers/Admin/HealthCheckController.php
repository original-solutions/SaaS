<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $status = [
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'broadcast' => $this->checkBroadcast(),
        ];

        $allHealthy = ! in_array(false, $status, true);

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $status,
        ], $allHealthy ? 200 : 503);
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkQueue(): bool
    {
        try {
            // Basic check — queue connection can be established
            app('queue')->connection();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkBroadcast(): bool
    {
        // Stub — Reverb is installed but not configured yet
        return true;
    }
}
