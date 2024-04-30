<?php

namespace Gricob\IMAP\Protocol\Command\Argument\Store;

use Gricob\IMAP\Protocol\Command\Argument\Argument;

final readonly class Flags implements Argument
{
    /**
     * @param list<string> $flags
     */
    public function __construct(
        private array $flags,
        private string $modifier = '',
        private bool $silent = true,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%sFLAGS%s (%s)',
            $this->modifier,
            $this->silent ? '.SILENT' : '',
            implode(' ', $this->flags),
        );
    }
}