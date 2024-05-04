<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructure;

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

    public static function tryParse(string $raw): ?self
    {
        return SinglePart::tryParse($raw) ?? MultiPart::tryParse($raw);
    }

    /**
     * @return array<string,string>|null
     */
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