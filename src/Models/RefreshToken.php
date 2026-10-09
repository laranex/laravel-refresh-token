<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Laranex\RefreshToken\Database\Factories\RefreshTokenFactory;

/**
 * @property string $id
 * @property string $refreshable_id
 * @property string $refreshable_type
 * @property bool $revoked
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $instance
 */
class RefreshToken extends Model
{
    /** @use HasFactory<RefreshTokenFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['id', 'refreshable_id', 'refreshable_type', 'revoked', 'expires_at', 'created_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * The primary key is a random hex string, not an auto-incrementing integer.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table is read from the package configuration.
     */
    public function getTable(): string
    {
        $table = Config::get('refresh-token.table');

        return is_string($table) && $table !== '' ? $table : 'laravel_refresh_tokens';
    }

    /**
     * The model the refresh token was issued for.
     *
     * @return MorphTo<Model, $this>
     */
    public function instance(): MorphTo
    {
        return $this->morphTo('refreshable');
    }

    /**
     * Revoke this refresh token.
     *
     * The update is atomic and only matches a token that is still active, so
     * when two requests race to rotate the same token only one of them gets
     * `true`; the other gets `false` and must reject the request.
     *
     * @return bool true when this call revoked the token, false when it was already revoked
     */
    public function revoke(): bool
    {
        $revoked = $this->newQuery()
            ->whereKey($this->getKey())
            ->where('revoked', false)
            ->update(['revoked' => true]) > 0;

        $this->forceFill(['revoked' => true])->syncOriginalAttribute('revoked');

        return $revoked;
    }

    /**
     * Revoke every refresh token issued to the same model as this one.
     */
    public function revokeAll(): int
    {
        return $this->newQuery()
            ->where('refreshable_id', $this->refreshable_id)
            ->where('refreshable_type', $this->refreshable_type)
            ->update(['revoked' => true]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RefreshTokenFactory
    {
        return RefreshTokenFactory::new();
    }
}
