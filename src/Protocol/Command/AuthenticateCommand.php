<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Authenticate\SASLMechanism;

readonly class AuthenticateCommand extends Command implements Continuable
{
    public function __construct(private SASLMechanism $mechanism)
    {
        parent::__construct('AUTHENTICATE', $mechanism);
    }

    public function continue(): string
    {
        return $this->mechanism->continue();
    }
}