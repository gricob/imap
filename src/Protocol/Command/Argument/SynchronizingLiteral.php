<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Argument;

final readonly class SynchronizingLiteral implements Argument
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return sprintf(
            '{%s}',
            strlen($this->value)
        );
    }
}