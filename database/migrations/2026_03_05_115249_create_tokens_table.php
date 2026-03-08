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
        // database/migrations/xxxx_create_tokens_table.php
        
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Храним только хеш токена, не сам токен — требование ТЗ
            $table->string('access_token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->unique();

            $table->boolean('is_revoked')->default(false);
            $table->boolean('refresh_used')->default(false); // был ли refresh токен уже использован

            $table->timestamp('access_expires_at');
            $table->timestamp('refresh_expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
