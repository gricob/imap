<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;

readonly class SinglePart extends Part
{
    /**
     * @param array<string,string> $attributes
     * @param string[]|null $language
     */
    public function __construct(
        string $type,
        string $subtype,
        array $attributes,
        public ?string $id,
        public ?string $description,
        public string $encoding,
        public int $size,
        public ?string $md5,
        public ?Disposition $disposition,
        public ?array $language,
        public ?string $location,
    ) {
        parent::__construct($type, $subtype, $attributes);
    }
}