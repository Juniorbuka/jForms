<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('jforms_responses', 'submission_token_hash')) {
            return;
        }

        Schema::table('jforms_responses', function (Blueprint $table): void {
            $table->char('submission_token_hash', 64)->nullable();
            $table->unique(['form_key', 'submission_token_hash'], 'jforms_submission_token_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('jforms_responses', 'submission_token_hash')) {
            return;
        }

        Schema::table('jforms_responses', function (Blueprint $table): void {
            $table->dropUnique('jforms_submission_token_unique');
            $table->dropColumn('submission_token_hash');
        });
    }
};
