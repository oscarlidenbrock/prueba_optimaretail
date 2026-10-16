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
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id();
            $table->foreignId('incident_id');
            $table->string('action', 64);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('user_name', 120);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // This index also supports the foreign key, without a redundant incident_id index.
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
        Schema::dropIfExists('incident_logs');
    }
};
