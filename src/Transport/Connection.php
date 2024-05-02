<?php

namespace Gricob\IMAP\Transport;

interface Connection
{
    public function isOpen(): bool;

    public function open(): void;

    public function close(): void;

    /**
     * @throws \Exception
     */
    public function send(string $data): void;

    /**
     * @throws \Exception
     */
    public function receive(): ResponseStream;
}