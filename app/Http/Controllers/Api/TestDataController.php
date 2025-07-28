<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Device;
use App\Models\Feedback;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TestDataController extends Controller
{
    public function generateTestData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'devices_count' => 'integer|min:1',
            'feedbacks_count' => 'integer|min:1',
            'feedbacks_per_device' => 'integer|min:1',
            'days_range' => 'integer|min:1|max:365',

            'feedback_types' => 'array',
            'feedback_types.*' => 'in:unsatisfied,neutral,satisfied',

            'unsatisfied_percentage' => 'integer|min:0',
            'neutral_percentage' => 'integer|min:0',
            'satisfied_percentage' => 'integer|min:0',

            'distribute_evenly' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation errors',
                'data' => $validator->errors()
            ], 422);
        }

        $customValidation = $this->validateFeedbackConfiguration($request);

        if (!$customValidation['valid']) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => $customValidation['message'],
                'data' => null
            ], 422);
        }

        $devicesCount = $request->get('devices_count', 5);
        $feedbacksCount = $request->get('feedbacks_count');
        $feedbacksPerDevice = $request->get('feedbacks_per_device', 50);
        $daysRange = $request->get('days_range', 30);
        $distributeEvenly = $request->get('distribute_evenly', true);

        try {
            $devices = $this->createTestDevices($devicesCount);

            $feedbackConfig = $this->prepareFeedbackConfiguration($request);

            $totalFeedbacks = $this->createTestFeedbacks(
                $devices,
                $feedbacksCount ?? $feedbacksPerDevice,
                $daysRange,
                $feedbackConfig,
                $distributeEvenly,
                $feedbacksCount ? true : false
            );

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Données de test générées avec succès',
                'data' => [
                    'devices_created' => count($devices),
                    'feedbacks_created' => $totalFeedbacks,
                    'feedback_distribution' => $this->getFeedbackDistribution($devices),
                    'configuration_used' => $feedbackConfig,
                    'devices' => $devices->map(function ($device) {
                        return [
                            'id' => $device->id,
                            'name' => $device->name,
                            'code' => $device->code,
                            'location' => $device->location
                        ];
                    })
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de la génération des données: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function addManualFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|uuid|exists:devices,id',
            'type' => 'required|in:unsatisfied,neutral,satisfied',
            'session_id' => 'nullable|string',
            'ip_address' => 'nullable|ip'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation errors',
                'data' => $validator->errors()
            ], 422);
        }

        try {
            $feedback = Feedback::create([
                'id' => Str::uuid(),
                'device_id' => $request->device_id,
                'type' => $request->type,
                'session_id' => $request->session_id ?? $this->generateSessionId(),
                'ip_address' => $request->ip_address ?? $request->ip()
            ]);

            $feedback->load('device');

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Feedback ajouté avec succès',
                'data' => [
                    'id' => $feedback->id,
                    'device_name' => $feedback->device->name,
                    'device_code' => $feedback->device->code,
                    'type' => $feedback->type,
                    'session_id' => $feedback->session_id,
                    'ip_address' => $feedback->ip_address,
                    'created_at' => $feedback->created_at
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de l\'ajout du feedback: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getConfigurationExamples()
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Configuration examples',
            'data' => [
                'examples' => [
                    'default_configuration' => [
                        'description' => 'Configuration par défaut avec répartition équilibrée',
                        'payload' => [
                            'devices_count' => 5,
                            'feedbacks_per_device' => 50,
                            'days_range' => 30
                        ]
                    ],
                    'custom_percentages' => [
                        'description' => 'Pourcentages personnalisés',
                        'payload' => [
                            'devices_count' => 3,
                            'feedbacks_count' => 200,
                            'days_range' => 15,
                            'unsatisfied_percentage' => 10,
                            'neutral_percentage' => 25,
                            'satisfied_percentage' => 65,
                            'distribute_evenly' => true
                        ]
                    ],
                    'only_satisfied' => [
                        'description' => 'Seulement des feedbacks satisfaits',
                        'payload' => [
                            'devices_count' => 2,
                            'feedbacks_per_device' => 30,
                            'days_range' => 7,
                            'feedback_types' => ['satisfied']
                        ]
                    ],
                    'only_neutral' => [
                        'description' => 'Seulement des feedbacks neutres',
                        'payload' => [
                            'devices_count' => 4,
                            'feedbacks_count' => 100,
                            'days_range' => 20,
                            'feedback_types' => ['neutral'],
                            'distribute_evenly' => false
                        ]
                    ],
                    'mixed_types' => [
                        'description' => 'Mélange de types spécifiques',
                        'payload' => [
                            'devices_count' => 3,
                            'feedbacks_per_device' => 40,
                            'days_range' => 10,
                            'feedback_types' => ['unsatisfied', 'satisfied']
                        ]
                    ],
                    'high_satisfaction' => [
                        'description' => 'Très haute satisfaction (90%)',
                        'payload' => [
                            'devices_count' => 5,
                            'feedbacks_count' => 500,
                            'days_range' => 60,
                            'unsatisfied_percentage' => 5,
                            'neutral_percentage' => 5,
                            'satisfied_percentage' => 90
                        ]
                    ]
                ],
                'parameters_documentation' => [
                    'devices_count' => 'Nombre de devices à créer (1-100)',
                    'feedbacks_count' => 'Nombre total de feedbacks à créer (distribués entre tous les devices)',
                    'feedbacks_per_device' => 'Nombre de feedbacks par device (utilisé si feedbacks_count n\'est pas fourni)',
                    'days_range' => 'Période en jours pour distribuer les feedbacks (1-365)',
                    'feedback_types' => 'Array des types spécifiques à générer: [\'unsatisfied\', \'neutral\', \'satisfied\']',
                    'unsatisfied_percentage' => 'Pourcentage de feedbacks négatifs (0-100)',
                    'neutral_percentage' => 'Pourcentage de feedbacks neutres (0-100)',
                    'satisfied_percentage' => 'Pourcentage de feedbacks positifs (0-100)',
                    'distribute_evenly' => 'Distribuer équitablement entre les devices (true/false)'
                ]
            ]
        ], 200);
    }
    public function getDevicesForTesting()
    {
        try {
            $devices = Device::select('id', 'name', 'code', 'location')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Devices récupérés',
                'data' => $devices
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de la récupération des devices: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function cleanTestData()
    {
        try {
            $feedbacksDeleted = Feedback::count();
            $devicesDeleted = Device::count();

            Feedback::truncate();

            Device::truncate();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Données de test supprimées avec succès',
                'data' => [
                    'feedbacks_deleted' => $feedbacksDeleted,
                    'devices_deleted' => $devicesDeleted
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    private function createTestDevices($count)
    {
        $devices = collect();
        $locations = [
            'Bureau principal',
            'Salle de réunion A - 1er étage',
            'Accueil - Entrée principale',
            'Cafétéria - 2ème étage',
            'Salle de formation - 3ème étage',
            'Open space - Aile Est',
            'Laboratoire - Sous-sol',
            'Terrasse - Toit'
        ];

        for ($i = 1; $i <= $count; $i++) {
            $device = Device::create([
                'id' => Str::uuid(),
                'name' => 'Device Test ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'code' => 'TEST-' . strtoupper(Str::random(6)),
                'location' => $locations[array_rand($locations)]
            ]);

            $devices->push($device);
        }

        return $devices;
    }

    private function createTestFeedbacks($devices, $feedbacksCount, $daysRange, $feedbackConfig, $distributeEvenly = true, $globalCount = false)
    {
        $totalFeedbacks = 0;

        if ($globalCount) {
            $totalFeedbacks = $this->createGlobalFeedbacks($devices, $feedbacksCount, $daysRange, $feedbackConfig, $distributeEvenly);
        } else {
            foreach ($devices as $device) {
                $deviceFeedbacks = $this->createDeviceFeedbacks($device, $feedbacksCount, $daysRange, $feedbackConfig);
                $totalFeedbacks += $deviceFeedbacks;
            }
        }

        return $totalFeedbacks;
    }

    private function createGlobalFeedbacks($devices, $totalCount, $daysRange, $feedbackConfig, $distributeEvenly)
    {
        $createdCount = 0;

        for ($i = 0; $i < $totalCount; $i++) {
            if ($distributeEvenly) {
                $device = $devices[$i % count($devices)];
            } else {
                $device = $devices[array_rand($devices->toArray())];
            }

            $randomDate = Carbon::now()->subDays(rand(0, $daysRange));

            $type = $this->selectFeedbackType($feedbackConfig);

            Feedback::create([
                'id' => Str::uuid(),
                'device_id' => $device->id,
                'type' => $type,
                'session_id' => $this->generateSessionId(),
                'ip_address' => $this->generateRandomIP(),
                'created_at' => $randomDate,
                'updated_at' => $randomDate
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    private function createDeviceFeedbacks($device, $feedbacksCount, $daysRange, $feedbackConfig)
    {
        $createdCount = 0;

        for ($i = 0; $i < $feedbacksCount; $i++) {
            $randomDate = Carbon::now()->subDays(rand(0, $daysRange));

            $type = $this->selectFeedbackType($feedbackConfig);

            Feedback::create([
                'id' => Str::uuid(),
                'device_id' => $device->id,
                'type' => $type,
                'session_id' => $this->generateSessionId(),
                'ip_address' => $this->generateRandomIP(),
                'created_at' => $randomDate,
                'updated_at' => $randomDate
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    private function validateFeedbackConfiguration($request)
    {
        $feedbackTypes = $request->get('feedback_types');
        $unsatisfiedPercentage = $request->get('unsatisfied_percentage');
        $neutralPercentage = $request->get('neutral_percentage');
        $satisfiedPercentage = $request->get('satisfied_percentage');

        if ($feedbackTypes && is_array($feedbackTypes) && !empty($feedbackTypes)) {
            return ['valid' => true];
        }

        if ($unsatisfiedPercentage !== null || $neutralPercentage !== null || $satisfiedPercentage !== null) {
            $total = ($unsatisfiedPercentage ?? 0) + ($neutralPercentage ?? 0) + ($satisfiedPercentage ?? 0);

            if ($total !== 100) {
                return [
                    'valid' => false,
                    'message' => 'La somme des pourcentages doit être égale à 100%. Total actuel: ' . $total . '%'
                ];
            }
        }

        return ['valid' => true];
    }

    private function prepareFeedbackConfiguration($request)
    {
        $feedbackTypes = $request->get('feedback_types');

        if ($feedbackTypes && is_array($feedbackTypes) && !empty($feedbackTypes)) {
            return [
                'mode' => 'specific_types',
                'types' => $feedbackTypes,
                'description' => 'Types spécifiques: ' . implode(', ', $feedbackTypes)
            ];
        }

        $unsatisfiedPercentage = $request->get('unsatisfied_percentage');
        $neutralPercentage = $request->get('neutral_percentage');
        $satisfiedPercentage = $request->get('satisfied_percentage');

        if ($unsatisfiedPercentage !== null || $neutralPercentage !== null || $satisfiedPercentage !== null) {
            $types = [];
            $weights = [];

            if (($unsatisfiedPercentage ?? 0) > 0) {
                $types[] = 'unsatisfied';
                $weights[] = $unsatisfiedPercentage;
            }
            if (($neutralPercentage ?? 0) > 0) {
                $types[] = 'neutral';
                $weights[] = $neutralPercentage;
            }
            if (($satisfiedPercentage ?? 0) > 0) {
                $types[] = 'satisfied';
                $weights[] = $satisfiedPercentage;
            }

            return [
                'mode' => 'weighted',
                'types' => $types,
                'weights' => $weights,
                'description' => sprintf(
                    'Pourcentages - Unsatisfied: %d%%, Neutral: %d%%, Satisfied: %d%%',
                    $unsatisfiedPercentage ?? 0,
                    $neutralPercentage ?? 0,
                    $satisfiedPercentage ?? 0
                )
            ];
        }

        return [
            'mode' => 'weighted',
            'types' => ['unsatisfied', 'neutral', 'satisfied'],
            'weights' => [20, 30, 50],
            'description' => 'Configuration par défaut - Unsatisfied: 20%, Neutral: 30%, Satisfied: 50%'
        ];
    }

    private function selectFeedbackType($config)
    {
        if ($config['mode'] === 'specific_types') {
            return $config['types'][array_rand($config['types'])];
        }

        if ($config['mode'] === 'weighted') {
            return $this->getWeightedRandomType($config['types'], $config['weights']);
        }

        return 'neutral';
    }

    private function getFeedbackDistribution($devices)
    {
        $deviceIds = $devices->pluck('id');

        $distribution = Feedback::whereIn('device_id', $deviceIds)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        $total = array_sum($distribution);

        $result = [];
        foreach (['unsatisfied', 'neutral', 'satisfied'] as $type) {
            $count = $distribution[$type] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            $result[$type] = [
                'count' => $count,
                'percentage' => $percentage
            ];
        }

        $result['total'] = $total;

        return $result;
    }

    private function getWeightedRandomType($types, $weights)
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        for ($i = 0; $i < count($types); $i++) {
            $currentWeight += $weights[$i];
            if ($random <= $currentWeight) {
                return $types[$i];
            }
        }

        return $types[0];
    }

    private function generateSessionId()
    {
        return 'sess_' . Str::random(20);
    }

    private function generateRandomIP()
    {
        return rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254);
    }
}
