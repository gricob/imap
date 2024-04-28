<?php

namespace Gricob\IMAP\Protocol\Command\Argument;

final readonly class SequenceSet implements Argument
{
    public function __construct(private int $from, private int $to)
    {
    }

    public function __toString(): string
    {
        return sprintf('%s:%s', $this->from, $this->to);
    }
}