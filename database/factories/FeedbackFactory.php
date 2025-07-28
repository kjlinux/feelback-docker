<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        $feedbackTypes = ['unsatisfied', 'neutral', 'satisfied'];

        return [
            'device_id' => Device::inRandomOrder()->first()?->id ?? 1,
            'type' => $this->faker->randomElement($feedbackTypes),
            'session_id' => 'sess_' . Str::random(20),
            'ip_address' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function satisfied(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'satisfied',
        ]);
    }

    public function neutral(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'neutral',
        ]);
    }

    public function unsatisfied(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'unsatisfied',
        ]);
    }
}
