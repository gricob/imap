<?php

namespace Gricob\IMAP\Protocol\Command;

abstract readonly class Command
{
    public string $command;
    public array $arguments;

    public function __construct(
        string $command,
        string ...$arguments,
    ) {
        $this->command = $command;
        $this->arguments = $arguments;
    }
}