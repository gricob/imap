<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Authenticate;

final readonly class XOAuth2 implements SASLMechanism
{
    public function __construct(
        private string $user,
        private string $accessToken
    ) {
    }

    public function __toString(): string
    {
        return 'XOAUTH2';
    }

    public function continue(): string
    {
        return base64_encode(
            sprintf("user=%s\1auth=Bearer %s\1\1", $this->user, $this->accessToken)
        );
    }
}