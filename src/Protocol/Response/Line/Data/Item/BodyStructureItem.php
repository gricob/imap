<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

use Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructure\Part;

class BodyStructureItem
{
    private const PATTERN = '/BODYSTRUCTURE (?<parts>\(([^()]|(?&parts))*\))/';

    public function __construct(
        public Part $part,
    ) {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        $part = Part::tryParse($matches['parts']);

        if (null === $part) {
            return null;
        }

        return new self($part);
    }
}