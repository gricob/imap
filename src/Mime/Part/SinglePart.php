<?php

namespace Gricob\IMAP\Mime\Part;

final readonly class SinglePart extends Part
{
    private string $encoding;

    public function __construct(
        string $type,
        string $subtype,
        array $attributes,
        private string $body,
        private string $charset,
        string $encoding,
        private ?Disposition $disposition,
    ) {

        $this->encoding = strtolower($encoding);
        parent::__construct($type, $subtype, $attributes);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function decodedBody(): string
    {
        return match ($this->encoding) {
            'quoted-printable' => quoted_printable_decode($this->body),
            default => $this->body,
        };
    }

    public function charset(): string
    {
        return $this->charset;
    }

    public function encoding(): string
    {
        return $this->encoding;
    }

    public function disposition(): ?Disposition
    {
        return $this->disposition;
    }

    public function findPartByMimeType(string $mimeType): ?SinglePart
    {
        if ($this->mimeType() === strtolower($mimeType)) {
            return $this;
        }

        return null;
    }
}