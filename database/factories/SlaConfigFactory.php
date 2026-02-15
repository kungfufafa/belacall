<?php

namespace Database\Factories;

use App\Enums\ReportPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlaConfig>
 */
class SlaConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'priority' => ReportPriority::MEDIUM,
            'response_time_minutes' => 240,
            'resolution_time_minutes' => 2880,
        ];
    }
}
