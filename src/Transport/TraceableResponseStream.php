<?php

namespace Gricob\IMAP\Transport;

use Psr\Log\LoggerInterface;

final readonly class TraceableResponseStream implements ResponseStream
{
    public function __construct(
        private ResponseStream $responseStream,
        private LoggerInterface $logger,
    ) {
    }

    public function read(int $bytes): string
    {
        $data = $this->responseStream->read($bytes);

        $this->debug($data);

        return $data;
    }

    public function readLine(): string
    {
        $line = $this->responseStream->readLine();

        $this->debug($line);

        return $line;
    }

    private function debug(string $data): void
    {
        //$data = addslashes($data);
        $data = str_replace("\r\n", "\\r\\n", $data);

        $this->logger->debug($data);
    }
}