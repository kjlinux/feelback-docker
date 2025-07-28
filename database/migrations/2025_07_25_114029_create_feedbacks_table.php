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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')
                ->constrained('devices')
                ->onDelete('cascade');

            $table->enum('type', ['unsatisfied', 'neutral', 'satisfied']);
            $table->string('session_id', 100)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('created_at', 'idx_feedbacks_created_at');
            $table->index(['device_id', 'created_at'], 'idx_feedbacks_device_date');
            $table->index('type', 'idx_feedbacks_type');
            $table->index('session_id', 'idx_feedbacks_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
