<?php

namespace Gricob\IMAP\Transport;

use Psr\Log\LoggerInterface;

final readonly class TraceableConnection implements Connection
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
    ) {
    }

    public function isOpen(): bool
    {
        return $this->connection->isOpen();
    }

    public function open(): void
    {
        $this->connection->open();
    }

    public function close(): void
    {
        $this->connection->close();
    }

    public function send(string $data): void
    {
        $this->debug(addslashes($data));

        $this->connection->send($data);
    }

    public function receive(): ResponseStream
    {
        return new TraceableResponseStream(
            $this->connection->receive(),
            $this->logger,
        );
    }

    private function debug(string $data): void
    {
        $data = str_replace("\r\n", "\\r\\n", $data);

        $this->logger->debug($data);
    }
}