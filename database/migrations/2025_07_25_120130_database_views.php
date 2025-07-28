<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW v_global_statistics AS
            SELECT
                COUNT(*) as total_feedbacks,
                COUNT(CASE WHEN type = 'satisfied' THEN 1 END) as satisfied_count,
                COUNT(CASE WHEN type = 'neutral' THEN 1 END) as neutral_count,
                COUNT(CASE WHEN type = 'unsatisfied' THEN 1 END) as unsatisfied_count,
                CASE
                    WHEN COUNT(*) = 0 THEN 0.00
                    ELSE ROUND((COUNT(CASE WHEN type = 'satisfied' THEN 1 END)::DECIMAL / COUNT(*)::DECIMAL) * 100, 2)
                END as satisfaction_rate
            FROM feedbacks
        ");

        DB::statement("
            CREATE VIEW v_device_statistics AS
            SELECT
                d.id,
                d.name,
                d.location,
                COUNT(f.id) as total_feedbacks,
                COUNT(CASE WHEN f.type = 'satisfied' THEN 1 END) as satisfied_count,
                COUNT(CASE WHEN f.type = 'neutral' THEN 1 END) as neutral_count,
                COUNT(CASE WHEN f.type = 'unsatisfied' THEN 1 END) as unsatisfied_count,
                CASE
                    WHEN COUNT(f.id) = 0 THEN 0.00
                    ELSE ROUND((COUNT(CASE WHEN f.type = 'satisfied' THEN 1 END)::DECIMAL / COUNT(f.id)::DECIMAL) * 100, 2)
                END as satisfaction_rate
            FROM devices d
            LEFT JOIN feedbacks f ON d.id = f.device_id
            GROUP BY d.id, d.name, d.location
        ");

        DB::statement(
            "
            CREATE VIEW v_daily_statistics AS
            SELECT
                DATE(created_at) as date,
                COUNT(*) as total_feedbacks,
                COUNT(CASE WHEN type = 'satisfied' THEN 1 END) as satisfied_count,
                COUNT(CASE WHEN type = 'neutral' THEN 1 END) as neutral_count,
                COUNT(CASE WHEN type = 'unsatisfied' THEN 1 END) as unsatisfied_count,
                ROUND((COUNT(CASE WHEN type = 'satisfied' THEN 1 END)::DECIMAL / COUNT(*)::DECIMAL) * 100, 2) as satisfaction_rate
            FROM feedbacks
            WHERE deleted_at IS NULL
            GROUP BY DATE(created_at)
            ORDER BY date DESC;
        "
        );

        DB::statement(
            "
            CREATE VIEW v_hourly_patterns AS
            SELECT
                EXTRACT(hour FROM created_at) as hour,
                COUNT(*) as total_feedbacks,
                ROUND(AVG(CASE
                    WHEN type = 'satisfied' THEN 3
                    WHEN type = 'neutral' THEN 2
                    WHEN type = 'unsatisfied' THEN 1
                END), 2) as avg_score,
                ROUND((COUNT(CASE WHEN type = 'satisfied' THEN 1 END)::DECIMAL / COUNT(*)::DECIMAL) * 100, 2) as satisfaction_rate
            FROM feedbacks
            WHERE deleted_at IS NULL
            GROUP BY EXTRACT(hour FROM created_at)
            ORDER BY hour;
            "
        );

        DB::statement(
            "
            CREATE VIEW v_device_activity_ranking AS
            SELECT
                d.id,
                d.name,
                d.code,
                d.location,
                COUNT(f.id) as total_feedbacks,
                COUNT(DISTINCT DATE(f.created_at)) as active_days,
                ROUND(COUNT(f.id)::DECIMAL / NULLIF(COUNT(DISTINCT DATE(f.created_at)), 0), 2) as avg_feedbacks_per_day,
                MAX(f.created_at) as last_feedback_date,
                ROUND((COUNT(CASE WHEN f.type = 'satisfied' THEN 1 END)::DECIMAL / NULLIF(COUNT(f.id), 0)::DECIMAL) * 100, 2) as satisfaction_rate
            FROM devices d
            LEFT JOIN feedbacks f ON d.id = f.device_id AND f.deleted_at IS NULL
            WHERE d.deleted_at IS NULL
            GROUP BY d.id, d.name, d.code, d.location
            ORDER BY total_feedbacks DESC;
            "
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_device_statistics');
        DB::statement('DROP VIEW IF EXISTS v_global_statistics');
    }
};
