<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;

final readonly class Disposition
{
    /**
     * @param array<string, string> $attributes
     */
    public function __construct(
        public string $type,
        public array $attributes,
    ) {
    }
}