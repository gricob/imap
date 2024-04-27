<?php

namespace Gricob\IMAP\Protocol\Command\Argument;

use DateTimeInterface;

readonly class Date implements Argument
{
    public function __construct(private DateTimeInterface $value)
    {
    }

    public static function tryFrom(?DateTimeInterface $value): ?self
    {
        return is_null($value) ? null : new self($value);
    }

    public function __toString(): string
    {
        return $this->value->format('d-M-Y');
    }
}