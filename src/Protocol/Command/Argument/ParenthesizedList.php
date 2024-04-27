<?php

namespace Gricob\IMAP\Protocol\Command\Argument;

final readonly class ParenthesizedList implements Argument
{
    public function __construct(public array $items)
    {
    }

    public static function tryFrom(?array $items): ?self
    {
        return empty($items) ? null : new self($items);
    }

    public function __toString(): string
    {
        return sprintf('(%s)', implode(' ', $this->items));
    }
}