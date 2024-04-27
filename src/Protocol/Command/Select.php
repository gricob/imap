<?php

namespace Gricob\IMAP\Protocol\Command;

readonly class Select extends Command
{
    public function __construct(string $mailbox)
    {
        parent::__construct('SELECT', $mailbox);
    }
}