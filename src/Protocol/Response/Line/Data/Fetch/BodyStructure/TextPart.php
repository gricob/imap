<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;

final readonly class TextPart extends SinglePart
{
    /**
     * @param array<string, string> $attributes
     * @param string[]|null $language
     */
    public function __construct(
        string $subtype,
        array $attributes,
        ?string $id,
        ?string $description,
        string $encoding,
        int $size,
        public int $textLines,
        ?string $md5,
        ?Disposition $disposition,
        ?array $language,
        ?string $location,
    ) {
        parent::__construct(
            'TEXT',
            $subtype,
            $attributes,
            $id,
            $description,
            $encoding,
            $size,
            $md5,
            $disposition,
            $language,
            $location,
        );
    }
}