<?php

namespace Gricob\IMAP\Mime;

use Gricob\IMAP\Mime\Part\Part;

class Message
{
    public function __construct(
        public int $id,
        public array $headers,
        public Part $body,
        public \DateTimeImmutable $internalDate,
    ) {
    }

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
        return $this->body()->findPartByMimeType('text/plain')?->body;
    }

    public function htmlBody(): ?string
    {
        return $this->body()->findPartByMimeType('text/html')?->body;
    }
}