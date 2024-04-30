<?php

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\DateTime;
use Gricob\IMAP\Protocol\Command\Argument\QuotedString;
use Gricob\IMAP\Protocol\Command\Argument\SynchronizingLiteral;
use Gricob\IMAP\Protocol\Command\Argument\ParenthesizedList;

final readonly class AppendCommand extends Command implements Continuable
{
    /**
     * @param list<string>|null $flags
     */
    public function __construct(
        string $mailboxName,
        private string $message,
        ?array $flags,
        ?\DateTimeInterface $internalDate
    ) {
        parent::__construct(
            'APPEND',
            ...array_filter([
                new QuotedString($mailboxName),
                ParenthesizedList::tryFrom($flags),
                DateTime::tryFrom($internalDate),
                new SynchronizingLiteral($this->message),
            ])
        );
    }

    public function continue(): string
    {
        return $this->message;
    }
}