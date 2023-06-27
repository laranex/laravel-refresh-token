<?php

namespace Laranex\RefreshToken\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class RefreshTokenFactory extends Factory
{
    public function __construct($count = null, ?Collection $states = null, ?Collection $has = null, ?Collection $for = null, ?Collection $afterMaking = null, ?Collection $afterCreating = null, $connection = null, ?Collection $recycle = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection, $recycle);
        $this->model = config('refresh-token.model');
    }

    public function definition(): array
    {
        return [
            'id' => bin2hex(random_bytes(40)),
            'refreshable_id' => $this->faker->uuid(),
            'refreshable_type' => 'App\Models\User',
            'issued_at' => Carbon::now(),
            'expires_at' => Carbon::now()->subYear(),
        ];
    }
}
