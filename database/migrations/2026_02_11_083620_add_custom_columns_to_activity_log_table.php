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
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('batch_uuid');
            $table->unsignedBigInteger('impersonator_id')->nullable()->after('tenant_id');
            $table->string('ip_address', 45)->nullable()->after('impersonator_id');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('request_id')->nullable()->after('user_agent');

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['tenant_id', 'impersonator_id', 'ip_address', 'user_agent', 'request_id']);
        });
    }
};
