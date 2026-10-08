<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Console\Commands;

use Illuminate\Console\Command;
use Laranex\RefreshToken\Clock;
use Laranex\RefreshToken\RefreshToken;

class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh-token:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all expired or revoked refresh tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pruned = RefreshToken::refreshTokenModel()::query()
            ->where('expires_at', '<', (new Clock)->now())
            ->orWhere('revoked', true)
            ->delete();

        $this->info(sprintf('Pruned %d refresh token(s).', $pruned));

        return self::SUCCESS;
    }
}
