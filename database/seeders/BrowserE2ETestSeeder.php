<?php

namespace Database\Seeders;

use App\Models\ParkingSpot;
use App\Models\Postalcode;
use App\Models\User;
use Illuminate\Database\Seeder;

class BrowserE2ETestSeeder extends Seeder
{
    /**
     * Seed a small, deterministic data set used only by the browser test job.
     */
    public function run(): void
    {
        $user = User::query()->where('user_id', 'e2e-user')->first()
            ?? User::factory()->create([
                'user_id' => 'e2e-user',
                'password' => 'password',
            ]);
        $postalcode = Postalcode::factory()->create();

        ParkingSpot::factory()
            ->count(51)
            ->sequence(fn ($sequence) => [
                'user_id' => $user->id,
                'postalcode_id' => $postalcode->id,
                'name' => sprintf('E2E 駐輪場 %02d', $sequence->index + 1),
                'latitude' => 35.680000 + ($sequence->index / 100000),
                'longitude' => 139.760000 + ($sequence->index / 100000),
                'is_published' => true,
            ])
            ->create();
    }
}
