<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable()->index();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sessions');
    }
};
