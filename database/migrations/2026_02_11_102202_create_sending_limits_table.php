<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sending_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->unsignedInteger('rate_per_minute')->default(60);
            $table->unsignedInteger('daily_cap')->default(1000);
            $table->unsignedInteger('sent_today')->default(0);
            $table->date('reset_date')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sending_limits');
    }
};
