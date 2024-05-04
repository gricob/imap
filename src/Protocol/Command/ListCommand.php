<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\QuotedString;

readonly class ListCommand extends Command
{
    public function __construct(string $referenceName, string $pattern)
    {
        parent::__construct(
            'LIST',
            new QuotedString($referenceName),
            new QuotedString($pattern)
        );
    }
}