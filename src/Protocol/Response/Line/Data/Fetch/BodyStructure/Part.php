<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;

abstract readonly class Part
{
    /**
     * @param array<string,string> $attributes
     */
    public function __construct(
        public string $type,
        public string $subtype,
        public array $attributes,
    ) {
    }
}