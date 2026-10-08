<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laranex\RefreshToken\Clock;
use Laranex\RefreshToken\Models\RefreshToken;

/**
 * @extends Factory<RefreshToken>
 */
class RefreshTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<RefreshToken>
     */
    protected $model = RefreshToken::class;

    /**
     * Define the model's default state: a valid token for a generic user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = (new Clock)->now();

        return [
            'id' => bin2hex(random_bytes(40)),
            'refreshable_id' => (string) $this->faker->randomNumber(5, true),
            'refreshable_type' => 'App\Models\User',
            'revoked' => false,
            'created_at' => $now,
            'expires_at' => $now->add(\Laranex\RefreshToken\RefreshToken::refreshTokensExpireIn()),
        ];
    }

    /**
     * Indicate that the token has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => (new Clock)->now()->modify('-1 day'),
        ]);
    }

    /**
     * Indicate that the token has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'revoked' => true,
        ]);
    }
}
