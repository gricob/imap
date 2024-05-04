<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\QuotedString;

final readonly class CreateCommand extends Command
{
    public function __construct(string $mailboxName)
    {
        parent::__construct('CREATE', new QuotedString($mailboxName));
    }
}