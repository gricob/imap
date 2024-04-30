<?php

namespace Gricob\IMAP\Protocol\Command\Argument;

final readonly class SequenceSet implements Argument
{
    /**
     * @var array<int>
     */
    private array $numbers;

    public function __construct(int ...$numbers)
    {
        $this->numbers = $numbers;
    }

    public function __toString(): string
    {
        return implode(',', $this->numbers);
    }
}