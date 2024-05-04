<?php

declare(strict_types=1);

namespace Gricob\IMAP\Transport;

use Exception;

interface Connection
{
    public function isOpen(): bool;

    public function open(): void;

    public function close(): void;

    /**
     * @throws Exception
     */
    public function send(string $data): void;

    /**
     * @throws Exception
     */
    public function receive(): ResponseStream;
}