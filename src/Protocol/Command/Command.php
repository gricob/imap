<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

use Gricob\IMAP\Protocol\Command\Argument\Argument;
use Stringable;

abstract readonly class Command implements Stringable
{
    private string $command;

    /**
     * @var Argument[]
     */
    private array $arguments;

    public function __construct(
        string $command,
        Argument ...$arguments,
    ) {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s',
            $this->command,
            implode(' ', $this->arguments)
        );
    }
}