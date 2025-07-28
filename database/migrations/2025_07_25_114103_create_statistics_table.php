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
        Schema::create('statistics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')
                ->constrained('devices')
                ->onDelete('cascade');

            $table->date('date_period');
            $table->enum('period_type', ['day', 'week', 'month']);
            $table->integer('satisfied_count')->default(0);
            $table->integer('neutral_count')->default(0);
            $table->integer('unsatisfied_count')->default(0);
            $table->integer('total_count')->default(0);
            $table->decimal('satisfaction_rate', 5, 2)->default(0.00);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['device_id', 'date_period', 'period_type'], 'unique_device_period');

            $table->index(['device_id', 'date_period', 'period_type'], 'idx_statistics_device_period');
            $table->index('date_period', 'idx_statistics_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
