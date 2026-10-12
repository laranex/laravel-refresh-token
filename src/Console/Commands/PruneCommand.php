<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Laranex\RefreshToken\Clock;
use Laranex\RefreshToken\RefreshToken;

class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh-token:prune
                            {--chunk=1000 : How many tokens to delete per query}';

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
        $chunk = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($chunk === false) {
            $this->error('The --chunk option must be a positive integer.');

            return self::INVALID;
        }

        $now = (new Clock)->now();

        // Two passes, each served by its own index, in chunks so a large table is never locked at once.
        $pruned = $this->deleteInChunks(fn (Builder $query): Builder => $query->where('revoked', true), $chunk)
            + $this->deleteInChunks(fn (Builder $query): Builder => $query->where('expires_at', '<', $now), $chunk);

        $this->info(sprintf('Pruned %d refresh token(s).', $pruned));

        return self::SUCCESS;
    }

    /**
     * Delete the matching tokens a chunk at a time and return how many were deleted.
     *
     * @param  callable(Builder<\Laranex\RefreshToken\Models\RefreshToken>): Builder<\Laranex\RefreshToken\Models\RefreshToken>  $constraint
     */
    private function deleteInChunks(callable $constraint, int $chunk): int
    {
        $model = RefreshToken::refreshTokenModel();
        $deleted = 0;

        do {
            $ids = $constraint($model::query())->limit($chunk)->pluck('id')->all();

            if ($ids === []) {
                break;
            }

            $deleted += $model::query()->whereKey($ids)->delete();
        } while (count($ids) === $chunk);

        return $deleted;
    }
}
