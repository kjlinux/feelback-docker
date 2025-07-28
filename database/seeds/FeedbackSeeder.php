<?php

namespace Database\Seeders;

use App\Models\Feedback;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Génération des feedbacks...');

        $chunkSize = 1000;
        $totalRecords = 35754;
        $chunks = ceil($totalRecords / $chunkSize);

        $progressBar = $this->command->getOutput()->createProgressBar($chunks);
        $progressBar->start();

        for ($i = 0; $i < $totalRecords; $i += $chunkSize) {
            $remaining = min($chunkSize, $totalRecords - $i);

            $satisfiedCount = (int)($remaining * 0.6);
            $neutralCount = (int)($remaining * 0.25);
            $unsatisfiedCount = $remaining - $satisfiedCount - $neutralCount;

            $feedbacks = [];

            // Générer les données en mémoire
            $feedbacks = array_merge($feedbacks, $this->generateFeedbacks($satisfiedCount, 'satisfied'));
            $feedbacks = array_merge($feedbacks, $this->generateFeedbacks($neutralCount, 'neutral'));
            $feedbacks = array_merge($feedbacks, $this->generateFeedbacks($unsatisfiedCount, 'unsatisfied'));

            // Insertion en lot
            DB::table('feedbacks')->insert($feedbacks);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nFeedbacks générés avec succès.");
    }

    private function generateFeedbacks(int $count, string $type): array
    {
        $feedbacks = [];
        $now = Carbon::now();

        for ($i = 0; $i < $count; $i++) {
            $feedbacks[] = [
                'device_id' => rand(1, 10), // Ajustez selon vos devices
                'rating' => $this->getRatingForType($type),
                'comment' => $this->getCommentForType($type),
                'created_at' => $now,
                'updated_at' => $now,
                // Ajoutez d'autres champs selon votre modèle
            ];
        }

        return $feedbacks;
    }

    private function getRatingForType(string $type): int
    {
        return match($type) {
            'satisfied' => rand(4, 5),
            'neutral' => 3,
            'unsatisfied' => rand(1, 2),
        };
    }

    private function getCommentForType(string $type): ?string
    {
        $comments = [
            'satisfied' => ['Excellent!', 'Très bon service', 'Je recommande'],
            'neutral' => ['Correct', 'Moyen', 'Ça va'],
            'unsatisfied' => ['Décevant', 'Pas terrible', 'À améliorer']
        ];

        return $comments[$type][array_rand($comments[$type])];
    }
}
