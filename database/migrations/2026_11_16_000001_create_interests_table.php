<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_id')->unique();
            $table->string('name', 100);
            $table->string('phone', 32)->index();
            $table->string('interest', 40)->index();
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('consented_at');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
