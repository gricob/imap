<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\SequenceSet;
use Gricob\IMAP\Protocol\Command\Argument\Store\Flags;

final readonly class StoreCommand extends Command
{
    public function __construct(
        bool $uid,
        SequenceSet $sequenceSet,
        Flags $dataItem
    ) {
        parent::__construct(
            $uid ? 'UID STORE' : 'STORE',
            $sequenceSet,
            $dataItem,
        );
    }
}