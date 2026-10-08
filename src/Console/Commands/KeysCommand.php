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

        file_put_contents($publicKey, $details['key']);
        file_put_contents($privateKey, $privateKeyContents);
        @chmod($privateKey, 0600);

        $this->info('Encryption keys generated successfully.');

        return self::SUCCESS;
    }
}
