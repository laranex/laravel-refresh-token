<?php

namespace Laranex\RefreshToken\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Laranex\RefreshToken\RefreshToken;
use phpseclib\Crypt\RSA as LegacyRSA;
use phpseclib3\Crypt\RSA;

class KeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh-token:keys
                                      {--force : Overwrite keys they already exist}
                                      {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the encryption keys for API authentication';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        [$publicKey, $privateKey] = [
            RefreshToken::keyPath('refresh-token-public.key'),
            RefreshToken::keyPath('refresh-token-private.key'),
        ];

        if ((file_exists($publicKey) || file_exists($privateKey)) && ! $this->option('force')) {
            $this->error('Encryption keys already exist. Use the --force option to overwrite them.');

            return 1;
        } else {
            if (class_exists(LegacyRSA::class)) {
                $keys = (new LegacyRSA)->createKey((int) $this->option('length'));

                file_put_contents($publicKey, Arr::get($keys, 'publickey'));
                file_put_contents($privateKey, Arr::get($keys, 'privatekey'));
            } else {
                $key = RSA::createKey((int) $this->option('length'));

                file_put_contents($publicKey, (string) $key->getPublicKey());
                file_put_contents($privateKey, (string) $key);
            }

            $this->info('Encryption keys generated successfully.');
        }

        return 0;
    }
}
