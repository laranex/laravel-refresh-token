<?php
/*
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create prune expired or revoked refresh tokens', closure: function () {
    $this->artisan('refresh-token:prune')
        ->assertExitCode(0);

    $expiredAtArray = [now()->subYear(), now()->addYear()];
    Laranex\RefreshToken\Models\RefreshToken::factory()->count(50)->create(['expires_at' => $expiredAtArray[array_rand($expiredAtArray)], 'revoked' => array_rand([0,1])]);




    $this->assertFileExists(storage_path('refresh-token-public.key'), storage_path('refresh-token-private.key'));
});*/
