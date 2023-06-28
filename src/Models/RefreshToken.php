<?php

namespace Laranex\RefreshToken\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RefreshToken extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'refreshable_id', 'refreshable_type', 'revoked', 'expires_at'];

    public function getTable()
    {
        return config('refresh-token.table', parent::getTable());
    }

    public function instance(): MorphTo
    {
        return $this->morphTo('refreshable');
    }

    /*
     * Revoke a refresh token
     */
    public function revoke(): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->update([
            'revoked' => true,
        ]);
    }

    /*
     * Revoke all refresh tokens which are related to the current token
     */
    public function revokeAll(): int
    {
        /** @phpstan-ignore-next-line */
        return $this->where('refreshable_id', $this->refreshable_id)
            /** @phpstan-ignore-next-line */
            ->where('refreshable_type', $this->refreshable_type)
            ->update(['revoked' => true]);

    }
}
