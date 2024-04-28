<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructure;

abstract readonly class Part
{
    public function __construct(
        public string $type,
        public string $subtype,
        public array $attributes,
    ) {
    }

    public static function tryParse(string $raw): ?static
    {
        return SinglePart::tryParse($raw) ?? MultiPart::tryParse($raw);
    }

    public static function tryParseAttributes(?string $raw): ?array
    {
        if (null === $raw) {
            return null;
        }

        preg_match_all('/\".*?\"/', $raw, $matches);
        $attributeList = $matches[0] ?? [];
        $attributes = [];
        for ($i = 0; $i < count($attributeList); $i += 2) {
            $attributes[trim($attributeList[$i], '"')] = trim($attributeList[$i + 1], '"');
        }

        return $attributes;
    }
}