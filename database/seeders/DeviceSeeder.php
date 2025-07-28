<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Device::create([
            'name' => 'Boîtier Accueil',
            'code' => 'FB001',
            'location' => 'Hall d\'accueil principal',
        ]);

        Device::create([
            'name' => 'Boîtier Caisse',
            'code' => 'FB002',
            'location' => 'Zone caisse',
        ]);

        Device::create([
            'name' => 'Boîtier Test',
            'code' => 'FB999',
            'location' => 'Environnement de test',
        ]);
    }
}
