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
        Schema::create('incidents', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->string('title', 255);
            $table->text('description');
            $table->string('status', 32)->default('open');
            $table->string('priority', 16)->default('medium');
            $table->string('requester_name', 120);
            $table->string('assigned_to', 120)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
