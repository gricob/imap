<?php

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\QuotedString;

final readonly class LogIn extends Command
{
    public function __construct(string $user, string $password)
    {
        parent::__construct(
            'LOGIN',
            new QuotedString($user),
            new QuotedString($password)
        );
    }
}