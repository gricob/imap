<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\SequenceSet;
use Gricob\IMAP\Protocol\Command\Argument\Store\Flags;

final readonly class StoreCommand extends Command
{
    public function __construct(
        SequenceSet $sequenceSet,
        Flags $dataItem
    ) {
        parent::__construct(
            'STORE',
            $sequenceSet,
            $dataItem,
        );
    }
}