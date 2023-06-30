<?php

namespace Laranex\RefreshToken\Traits;

use DateTimeImmutable;
use Laranex\RefreshToken\RefreshToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

trait HasRefreshTokens
{
    /**
     * Create a refresh token
     */
    public function createRefreshToken(): string
    {
        $issuedAt = new DateTimeImmutable();
        $expiredAt = $issuedAt->add(RefreshToken::refreshTokensExpireIn());
        $tokenId = bin2hex(random_bytes(40));

        RefreshToken::refreshTokenModel()::create([
            'id' => $tokenId,
            'refreshable_id' => $this->getKey(),
            'refreshable_type' => get_class($this),
            'created_at' => $issuedAt,
            'expires_at' => $expiredAt,
        ]);

        return (new JwtFacade())->issue(
            new Sha256(),
            InMemory::plainText(RefreshToken::makeCryptKey('private')->getKeyContents()),
            function (Builder $builder) use ($expiredAt, $tokenId, $issuedAt) {
                return $builder
                    ->issuedAt($issuedAt)
                    ->expiresAt($expiredAt)
                    ->identifiedBy($tokenId)
                    ->relatedTo($this->getKey());
            })->toString();
    }
}
