<?php

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\SequenceSet;

final readonly class FetchCommand extends Command
{
    public function __construct(
        bool $uid,
        SequenceSet $sequenceSet,
        array $items,
    ) {
        parent::__construct(
            $uid ? 'UID FETCH' : 'FETCH',
            $sequenceSet,
            '('.implode(' ', $items).')'
        );
    }
}