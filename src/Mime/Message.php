<?php

namespace Gricob\IMAP\Mime;

use Gricob\IMAP\Mime\Part\MultiPart;
use Gricob\IMAP\Mime\Part\Part;
use Gricob\IMAP\Mime\Part\SinglePart;

class Message
{
    public function __construct(
        public array $headers,
        public Part $body,
        public \DateTimeImmutable $internalDate,
    ) {
    }

    public function textBody(): ?string
    {
        return $this->body->findPartByMimeType('text/plain')?->body;
    }

    public function htmlBody(): ?string
    {
        return $this->body->findPartByMimeType('text/html')?->body;
    }
}