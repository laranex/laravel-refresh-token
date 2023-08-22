<?php

namespace Laranex\RefreshToken\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laranex\RefreshToken\RefreshToken;
use Laranex\RefreshToken\Tests\TestCase;

class PruneCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_prune_revoked_and_expired_tokens(): void
    {
        $refreshTokenModel = RefreshToken::refreshTokenModel();
        $expiresAtArray = [now()->subDay(), now()->addDay()];
        $revokedArray = [true, false];

        $refreshTokenModel::factory()->count(500)->create([
            'revoked' => $revokedArray[array_rand($revokedArray)],
            'expires_at' => $expiresAtArray[array_rand($expiresAtArray)],
        ]);

        $this->artisan('refresh-token:prune');

        $invalidTokenCount = $refreshTokenModel::where('revoked', true)->orWhere('expires_at', '<', now())->count();
        $this->assertEquals(0, $invalidTokenCount);
    }
}
