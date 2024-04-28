<?php

namespace Gricob\IMAP\Mime\Part;

final readonly class SinglePart extends Part
{
    public function __construct(
        string $type,
        string $subtype,
        array $attributes,
        public string $body,
        public string $charset,
        public string $encoding,
        public ?Disposition $disposition,
    ) {
        parent::__construct($type, $subtype, $attributes);
    }

    public function findPartByMimeType(string $mimeType): ?SinglePart
    {
        if ($this->mimeType() === strtolower($mimeType)) {
            return $this;
        }

        return null;
    }
}