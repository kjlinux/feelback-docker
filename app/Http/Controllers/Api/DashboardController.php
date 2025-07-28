<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * 📊 INSIGHT 1: Statistiques globales de satisfaction
     * Type: KPI Cards + Gauge Chart
     * Graphique Highcharts: solidgauge (jauge) + basic cards
     */
    public function getGlobalStatistics()
    {
        $stats = DB::table('v_global_statistics')->first();

        return response()->json([
            'total_feedbacks' => $stats->total_feedbacks ?? 0,
            'satisfied_count' => $stats->satisfied_count ?? 0,
            'neutral_count' => $stats->neutral_count ?? 0,
            'unsatisfied_count' => $stats->unsatisfied_count ?? 0,
            'satisfaction_rate' => $stats->satisfaction_rate ?? 0,
            'chart_data' => [
                'satisfaction_gauge' => [
                    'value' => $stats->satisfaction_rate ?? 0,
                    'title' => 'Taux de satisfaction'
                ],
                'distribution_pie' => [
                    ['name' => 'Satisfait', 'y' => $stats->satisfied_count ?? 0, 'color' => '#28a745'],
                    ['name' => 'Neutre', 'y' => $stats->neutral_count ?? 0, 'color' => '#ffc107'],
                    ['name' => 'Insatisfait', 'y' => $stats->unsatisfied_count ?? 0, 'color' => '#dc3545']
                ]
            ]
        ]);
    }

    /**
     * 📈 INSIGHT 2: Tendances temporelles (jour/semaine/mois)
     * Type: Line Chart avec filtres temporels
     * Graphique Highcharts: line chart avec zoom et sélection période
     */
    public function getTemporalTrends(Request $request)
    {
        $period = $request->input('period', 'daily');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30));
        $endDate = $request->input('end_date', Carbon::now());

        $query = "SELECT ";

        switch ($period) {
            case 'weekly':
                $query .= "
                    DATE_TRUNC('week', date) as period,
                    SUM(total_feedbacks) as total_feedbacks,
                    SUM(satisfied_count) as satisfied_count,
                    SUM(neutral_count) as neutral_count,
                    SUM(unsatisfied_count) as unsatisfied_count,
                    ROUND(AVG(satisfaction_rate), 2) as satisfaction_rate
                FROM v_daily_statistics
                WHERE date BETWEEN ? AND ?
                GROUP BY DATE_TRUNC('week', date)
                ORDER BY period";
                break;

            case 'monthly':
                $query .= "
                    DATE_TRUNC('month', date) as period,
                    SUM(total_feedbacks) as total_feedbacks,
                    SUM(satisfied_count) as satisfied_count,
                    SUM(neutral_count) as neutral_count,
                    SUM(unsatisfied_count) as unsatisfied_count,
                    ROUND(AVG(satisfaction_rate), 2) as satisfaction_rate
                FROM v_daily_statistics
                WHERE date BETWEEN ? AND ?
                GROUP BY DATE_TRUNC('month', date)
                ORDER BY period";
                break;

            default:
                $query .= "
                    date as period,
                    total_feedbacks,
                    satisfied_count,
                    neutral_count,
                    unsatisfied_count,
                    satisfaction_rate
                FROM v_daily_statistics
                WHERE date BETWEEN ? AND ?
                ORDER BY date";
        }

        $data = DB::select($query, [$startDate, $endDate]);

        $chartData = [
            'categories' => [],
            'series' => [
                [
                    'name' => 'Taux de satisfaction (%)',
                    'type' => 'spline',
                    'yAxis' => 1,
                    'data' => [],
                    'color' => '#28a745'
                ],
                [
                    'name' => 'Total feedbacks',
                    'type' => 'column',
                    'data' => [],
                    'color' => '#007bff'
                ]
            ]
        ];

        foreach ($data as $item) {
            $chartData['categories'][] = Carbon::parse($item->period)->format(
                $period === 'daily' ? 'd/m' : ($period === 'weekly' ? 'W\WY' : 'm/Y')
            );
            $chartData['series'][0]['data'][] = (float) $item->satisfaction_rate;
            $chartData['series'][1]['data'][] = (int) $item->total_feedbacks;
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Feedback ajouté avec succès',
            'data' => $chartData
        ]);
    }

    /**
     * 📱 INSIGHT 3: Performance par dispositif
     * Type: Bar Chart horizontal + DataTable
     * Graphique Highcharts: bar chart avec drill-down
     */
    public function getDevicePerformance()
    {
        $devices = DB::table('v_device_activity_ranking')
            ->orderBy('satisfaction_rate', 'desc')
            ->get();

        $chartData = [
            'categories' => [],
            'series' => [
                [
                    'name' => 'Taux de satisfaction (%)',
                    'data' => [],
                    'color' => '#28a745'
                ],
                [
                    'name' => 'Total feedbacks',
                    'data' => [],
                    'color' => '#17a2b8',
                    'yAxis' => 1
                ]
            ]
        ];

        $tableData = [];

        foreach ($devices as $device) {
            $chartData['categories'][] = $device->code;
            $chartData['series'][0]['data'][] = (float) $device->satisfaction_rate;
            $chartData['series'][1]['data'][] = (int) $device->total_feedbacks;

            $tableData[] = [
                'id' => $device->id,
                'name' => $device->code,
                'location' => $device->location,
                'total_feedbacks' => $device->total_feedbacks,
                'satisfaction_rate' => $device->satisfaction_rate,
                'avg_feedbacks_per_day' => $device->avg_feedbacks_per_day,
                'last_feedback_date' => $device->last_feedback_date,
                'status' => $this->getDeviceStatus($device)
            ];
        }

        return response()->json([
            'chart_data' => $chartData,
            'table_data' => $tableData
        ]);
    }

    /**
     * ⏰ INSIGHT 4: Patterns horaires d'utilisation
     * Type: Heatmap ou Column Chart
     * Graphique Highcharts: column chart avec gradient colors
     */
    public function getHourlyPatterns()
    {
        $patterns = DB::table('v_hourly_patterns')
            ->orderBy('hour')
            ->get();

        $chartData = [
            'categories' => [],
            'series' => [
                [
                    'name' => 'Nombre de feedbacks',
                    'data' => [],
                    'type' => 'column'
                ],
                [
                    'name' => 'Score moyen',
                    'data' => [],
                    'type' => 'spline',
                    'yAxis' => 1,
                    'color' => '#ff6b6b'
                ]
            ]
        ];

        foreach ($patterns as $pattern) {
            $hour = str_pad($pattern->hour, 2, '0', STR_PAD_LEFT) . ':00';
            $chartData['categories'][] = $hour;
            $chartData['series'][0]['data'][] = [
                'y' => (int) $pattern->total_feedbacks,
                'color' => $this->getHourColorByActivity($pattern->total_feedbacks)
            ];
            $chartData['series'][1]['data'][] = (float) $pattern->avg_score;
        }

        return response()->json($chartData);
    }

    /**
     * 🎯 INSIGHT 5: Distribution des sentiments (Pie Chart détaillé)
     * Type: Pie Chart avec drill-down par période
     * Graphique Highcharts: pie chart avec drill-down
     */
    public function getSentimentDistribution(Request $request)
    {
        $period = $request->input('period', 7);

        $startDate = Carbon::now()->subDays($period)->toDateTimeString();

        $data = DB::table('feedbacks')
            ->select([
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw("ROUND((COUNT(*)::DECIMAL / (SELECT COUNT(*) FROM feedbacks WHERE created_at >= '{$startDate}' AND deleted_at IS NULL)::DECIMAL) * 100, 2) as percentage")
            ])
            ->where('created_at', '>=', $startDate)
            ->whereNull('deleted_at')
            ->groupBy('type')
            ->get();

        $chartData = [];
        $colors = [
            'satisfied'   => '#28a745',
            'neutral'     => '#ffc107',
            'unsatisfied' => '#dc3545'
        ];

        $labels = [
            'satisfied'   => 'Satisfait',
            'neutral'     => 'Neutre',
            'unsatisfied' => 'Insatisfait'
        ];

        foreach ($data as $item) {
            $chartData[] = [
                'name'      => $labels[$item->type],
                'y'         => (int) $item->count,
                'percentage' => (float) $item->percentage,
                'color'     => $colors[$item->type],
                'drilldown' => $item->type
            ];
        }

        return response()->json([
            'series' => [[
                'name'         => 'Feedbacks',
                'colorByPoint' => true,
                'data'         => $chartData
            ]]
        ]);
    }

    /**
     * 🚨 INSIGHT 6: Alertes et dispositifs problématiques
     * Type: Alert Cards + Table
     */
    public function getAlerts()
    {
        $alerts = [];


        $lowSatisfaction = DB::table('v_device_activity_ranking')
            ->where('satisfaction_rate', '<', 60)
            ->where('total_feedbacks', '>', 5)
            ->get();

        foreach ($lowSatisfaction as $device) {
            $alerts[] = [
                'type' => 'low_satisfaction',
                'severity' => 'high',
                'title' => 'Faible taux de satisfaction',
                'message' => "Le dispositif {$device->name} ({$device->location}) a un taux de satisfaction de {$device->satisfaction_rate}%",
                'device_id' => $device->id,
                'value' => $device->satisfaction_rate
            ];
        }


        $inactiveDevices = DB::table('v_device_activity_ranking')
            ->where('last_feedback_date', '<', Carbon::now()->subDays(7))
            ->orWhereNull('last_feedback_date')
            ->get();

        foreach ($inactiveDevices as $device) {
            $daysSinceLastFeedback = $device->last_feedback_date
                ? Carbon::parse($device->last_feedback_date)->diffInDays(Carbon::now())
                : 'Jamais';

            $alerts[] = [
                'type' => 'inactive',
                'severity' => 'medium',
                'title' => 'Dispositif inactif',
                'message' => "Le dispositif {$device->name} ({$device->location}) n'a pas reçu de feedback depuis {$daysSinceLastFeedback} jours",
                'device_id' => $device->id,
                'value' => $daysSinceLastFeedback
            ];
        }

        return response()->json($alerts);
    }

    /**
     * 📊 INSIGHT 7: Dashboard complet (toutes les métriques)
     * Type: Compilation de tous les insights
     */
    public function getDashboardData(Request $request)
    {
        return response()->json([
            'global_stats' => $this->getGlobalStatistics()->getData(),
            'temporal_trends' => $this->getTemporalTrends($request)->getData(),
            'device_performance' => $this->getDevicePerformance()->getData(),
            'hourly_patterns' => $this->getHourlyPatterns()->getData(),
            'sentiment_distribution' => $this->getSentimentDistribution($request)->getData(),
            'alerts' => $this->getAlerts()->getData(),
            'last_updated' => Carbon::now()->toISOString()
        ]);
    }


    private function getDeviceStatus($device)
    {
        if ($device->total_feedbacks == 0) return 'inactive';
        if ($device->satisfaction_rate < 50) return 'critical';
        if ($device->satisfaction_rate < 70) return 'warning';
        return 'good';
    }

    private function getHourColorByActivity($count)
    {
        if ($count > 50) return '#28a745';
        if ($count > 20) return '#ffc107';
        if ($count > 5) return '#fd7e14';
        return '#dc3545';
    }
}
