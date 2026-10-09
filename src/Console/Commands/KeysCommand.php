<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Console\Commands;

use Illuminate\Console\Command;
use Laranex\RefreshToken\RefreshToken;

class KeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh-token:keys
                            {--force : Overwrite keys if they already exist}
                            {--length=4096 : The length of the RSA private key in bits (at least 2048)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the RSA keys used to sign and verify refresh tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $length = (int) $this->option('length');

        if ($length < 2048) {
            $this->error('The key length must be at least 2048 bits.');

            return self::FAILURE;
        }

        $publicKey = RefreshToken::keyPath('refresh-token-public.key');
        $privateKey = RefreshToken::keyPath('refresh-token-private.key');

        if ((file_exists($publicKey) || file_exists($privateKey)) && ! $this->option('force')) {
            $this->error('Encryption keys already exist. Use the --force option to overwrite them.');

            return self::FAILURE;
        }

        $key = openssl_pkey_new([
            'private_key_bits' => $length,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $details = $key === false ? false : openssl_pkey_get_details($key);

        if ($key === false || $details === false || ! openssl_pkey_export($key, $privateKeyContents)) {
            $this->error('Unable to generate an RSA key pair with OpenSSL.');

            return self::FAILURE;
        }

        $directory = dirname($privateKey);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error("Unable to create the key directory [{$directory}].");

            return self::FAILURE;
        }

        // Restrict the private key to its owner before its contents are written.
        if (! $this->createPrivateFile($privateKey) || @file_put_contents($privateKey, $privateKeyContents) === false) {
            $this->error("Unable to write the private key to [{$privateKey}].");

            return self::FAILURE;
        }

        if (@file_put_contents($publicKey, $details['key']) === false) {
            @unlink($privateKey);
            $this->error("Unable to write the public key to [{$publicKey}].");

            return self::FAILURE;
        }

        $this->info('Encryption keys generated successfully.');

        return self::SUCCESS;
    }

    /**
     * Create the file if needed and restrict it to its owner.
     */
    private function createPrivateFile(string $path): bool
    {
        if (is_dir($path)) {
            return false;
        }

        $umask = umask(0077);

        try {
            if (@touch($path) === false) {
                return false;
            }
        } finally {
            umask($umask);
        }

        @chmod($path, 0600);

        return true;
    }
}
