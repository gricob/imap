<?php

namespace Gricob\IMAP\Mime;

use Gricob\IMAP\Mime\Part\Part;

class Message
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        protected int $id,
        protected array $headers,
        protected Part $body,
        protected \DateTimeImmutable $internalDate,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): Part
    {
        return $this->body;
    }

    public function internalDate(): \DateTimeImmutable
    {
        return $this->internalDate;
    }

    public function textBody(): ?string
    {
        return $this->body()->findPartByMimeType('text/plain')?->decodedBody();
    }

    public function htmlBody(): ?string
    {
        return $this->body()->findPartByMimeType('text/html')?->decodedBody();
    }
}