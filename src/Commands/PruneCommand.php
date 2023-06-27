<?php

namespace Laranex\RefreshToken\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Delete all expired or revoked refresh tokens.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        RefreshToken::refreshTokenModel()::where('expires_at', '<', now())
            ->orWhere('revoked', true)
            ->delete();

        return 0;
    }
}
