<?php

namespace Database\Factories;

use App\Enums\DataExportStatus;
use App\Models\DataExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataExport>
 */
class DataExportFactory extends Factory
{
    protected $model = DataExport::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => DataExportStatus::Pending,
            'disk' => (string) config('privacy.export_disk'),
            'path' => null,
            'size' => null,
            'completed_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * A finished export that is still downloadable.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataExportStatus::Ready,
            'path' => 'exports/'.fake()->uuid().'.zip',
            'size' => 1024,
            'completed_at' => now(),
            'expires_at' => now()->addHours((int) config('privacy.export_ttl_hours')),
        ]);
    }
}
