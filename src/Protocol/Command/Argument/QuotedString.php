<?php

namespace Gricob\IMAP\Protocol\Command\Argument;

final readonly class QuotedString implements Argument
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return sprintf('"%s"', $this->value);
    }
}