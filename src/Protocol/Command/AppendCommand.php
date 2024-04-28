<?php

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\Date;
use Gricob\IMAP\Protocol\Command\Argument\QuotedString;
use Gricob\IMAP\Protocol\Command\Argument\SynchronizingLiteral;
use Gricob\IMAP\Protocol\Command\Argument\ParenthesizedList;

final readonly class AppendCommand extends Command implements Continuable
{
    public function __construct(
        string $mailboxName,
        private string $message,
        ?array $flags,
        ?\DateTime $internalDate
    ) {
        parent::__construct(
            'APPEND',
            ...array_filter([
                new QuotedString($mailboxName),
                ParenthesizedList::tryFrom($flags),
                Date::tryFrom($internalDate),
                new SynchronizingLiteral($this->message),
            ])
        );
    }

    public function continue(): string
    {
        return $this->message;
    }
}