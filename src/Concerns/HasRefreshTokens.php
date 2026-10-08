<?php

declare(strict_types=1);

namespace Laranex\RefreshToken\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laranex\RefreshToken\Clock;
use Laranex\RefreshToken\Models\RefreshToken as RefreshTokenModel;
use Laranex\RefreshToken\RefreshToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use LogicException;

/**
 * @phpstan-require-extends Model
 */
trait HasRefreshTokens
{
    /**
     * Issue a new refresh token for this model and return it as a signed JWT.
     *
     * @throws LogicException when the model has not been saved yet
     */
    public function createRefreshToken(): string
    {
        $subject = (string) $this->getKey();

        if ($subject === '') {
            throw new LogicException('A refresh token can only be issued for a model that has been saved.');
        }

        $issuedAt = (new Clock)->now();
        $expiresAt = $issuedAt->add(RefreshToken::refreshTokensExpireIn());
        $tokenId = bin2hex(random_bytes(40));

        RefreshToken::refreshTokenModel()::query()->create([
            'id' => $tokenId,
            'refreshable_id' => $subject,
            'refreshable_type' => $this->getMorphClass(),
            'created_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);

        return (new JwtFacade(clock: new Clock))->issue(
            new Sha256,
            InMemory::plainText(RefreshToken::keyContents('private')),
            fn (Builder $builder): Builder => $builder
                ->issuedAt($issuedAt)
                ->canOnlyBeUsedAfter($issuedAt)
                ->expiresAt($expiresAt)
                ->identifiedBy($tokenId)
                ->relatedTo($subject),
        )->toString();
    }

    /**
     * Every refresh token that has been issued for this model.
     *
     * @return MorphMany<RefreshTokenModel, $this>
     */
    public function refreshTokens(): MorphMany
    {
        return $this->morphMany(RefreshToken::refreshTokenModel(), 'refreshable');
    }
}
