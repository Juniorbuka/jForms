<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('jforms_responses', 'idempotency_key')) {
            return;
        }

        Schema::table('jforms_responses', function (Blueprint $table): void {
            $table->dropUnique('jforms_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('jforms_responses', 'idempotency_key')) {
            return;
        }

        Schema::table('jforms_responses', function (Blueprint $table): void {
            $table->char('idempotency_key', 36)->nullable();
            $table->unique(['form_key', 'idempotency_key'], 'jforms_idempotency_unique');
        });
    }
};
