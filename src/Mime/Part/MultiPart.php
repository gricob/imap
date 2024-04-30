<?php

namespace Gricob\IMAP\Mime\Part;

final readonly class MultiPart extends Part
{
    /**
     * @param array<string,string> $attributes
     * @param list<Part> $parts
     */
    public function __construct(
        string $subtype,
        array $attributes,
        public array $parts,
    ) {
        parent::__construct('multipart', $subtype, $attributes);
    }

    public function findPartByMimeType(string $mimeType): ?SinglePart
    {
        foreach ($this->parts as $part) {
            if ($matchedPart = $part->findPartByMimeType(strtolower($mimeType))) {
                return $matchedPart;
            }
        }

        return null;
    }
}