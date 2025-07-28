<?php

namespace Database\Seeders;

use App\Models\Feedback;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Génération des feedbacks...');

        DB::statement('SET session_replication_role = replica;');

        $chunkSize = 1000;
        $totalRecords = 154658;
        $chunks = $totalRecords / $chunkSize;

        $progressBar = $this->command->getOutput()->createProgressBar($chunks);
        $progressBar->start();

        for ($i = 0; $i < $totalRecords; $i += $chunkSize) {
            $satisfiedCount = (int)($chunkSize * 0.6);
            $neutralCount = (int)($chunkSize * 0.25);
            $unsatisfiedCount = $chunkSize - $satisfiedCount - $neutralCount;

            Feedback::factory($satisfiedCount)->satisfied()->create();
            Feedback::factory($neutralCount)->neutral()->create();
            Feedback::factory($unsatisfiedCount)->unsatisfied()->create();

            $progressBar->advance();
        }

        $progressBar->finish();

        DB::statement('SET session_replication_role = DEFAULT;');

        $this->command->info("\n Feedbacks générés avec succès.");
    }
}
