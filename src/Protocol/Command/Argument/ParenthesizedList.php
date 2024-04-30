<?php

namespace Gricob\IMAP\Protocol\Command\Argument;

final readonly class ParenthesizedList implements Argument
{
    /**
     * @param list<string> $items
     */
    public function __construct(public array $items)
    {
    }

    /**
     * @param list<string> $items
     */
    public static function tryFrom(?array $items): ?self
    {
        return empty($items) ? null : new self($items);
    }

    public function __toString(): string
    {
        return sprintf('(%s)', implode(' ', $this->items));
    }
}