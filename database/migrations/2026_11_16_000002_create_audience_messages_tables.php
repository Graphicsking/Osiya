<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title', 100);
            $table->text('body');
            $table->json('filters');
            $table->string('status', 20)->default('draft');
            $table->uuid('review_token')->nullable();
            $table->unsignedInteger('excluded_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audience_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('status', 20)->default('pending');
            $table->string('provider_id')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();
            $table->unique(['audience_message_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
        Schema::dropIfExists('audience_messages');
    }
};
