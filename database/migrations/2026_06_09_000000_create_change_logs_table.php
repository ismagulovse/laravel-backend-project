<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('before');
            $table->json('after');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by');

            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('created_by');

            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
