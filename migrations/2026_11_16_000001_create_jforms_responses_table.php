<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jforms_responses', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('form_key', 100)->index();
            $table->string('form_name', 190);
            $table->json('data');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['form_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jforms_responses');
    }
};
