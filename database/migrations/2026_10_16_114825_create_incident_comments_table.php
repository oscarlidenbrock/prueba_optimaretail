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
        Schema::create('incident_comments', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('incident_id');
            $table->string('author_name', 120);
            $table->text('body');
            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
            $table->foreign('incident_id')->references('id')->on('incidents')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_comments');
    }
};
