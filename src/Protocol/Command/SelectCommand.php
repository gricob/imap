<?php

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\QuotedString;

readonly class SelectCommand extends Command
{
    public function __construct(string $mailbox)
    {
        parent::__construct('SELECT', new QuotedString($mailbox));
    }
}