<?php

namespace Laranex\RefreshToken\Tests\Feature;

use Laranex\RefreshToken\Tests\TestCase;

class KeysCommandTest extends TestCase
{
    /** @test */
    public function it_can_create_public_and_private_key_files(): void
    {
        if (($code = $this->artisan('refresh-token:keys')->execute()) !== 0) {
            $code = $this->artisan('refresh-token:keys --force')->execute();
        }

        $this->assertEquals(0, $code);
        $this->assertFileExists(storage_path('refresh-token-public.key'), storage_path('refresh-token-private.key'));
    }
}
