<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;

final readonly class MultiPart extends Part
{
    /**
     * @param array<string,string> $attributes
     * @param string[] $language
     * @param list<Part> $parts
     */
    public function __construct(
        string $subtype,
        array $attributes,
        public array $parts,
        public ?Disposition $disposition,
        public ?array $language,
        public ?string $location,
    ) {
        parent::__construct('MULTIPART', $subtype, $attributes);
    }
}