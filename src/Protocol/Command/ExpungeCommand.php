<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

final readonly class ExpungeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('EXPUNGE');
    }
}