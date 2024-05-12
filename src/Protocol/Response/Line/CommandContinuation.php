<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line;

final readonly class CommandContinuation implements Line
{
    public function __construct(
        public string $message,
    ) {
    }
}