<?php

it('can create public and private key files', function () {
    if ($code = $this->artisan('refresh-token:keys')->execute() !== 0) {
        $code = $this->artisan('refresh-token:keys --force')->execute();
    }

    expect($code)->toBe(0);
    $this->assertFileExists(storage_path('refresh-token-public.key'), storage_path('refresh-token-private.key'));
});
