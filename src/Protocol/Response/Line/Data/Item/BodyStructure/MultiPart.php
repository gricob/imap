<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructure;

final readonly class MultiPart extends Part
{
    private const PATTERN = '/(?<parts>(?<part>\(([^()]|(?&part))*\))+) \"(?<subtype>.*?)\" \((?<attributes>.*)\)/';

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

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        preg_match_all('/\(([^()]|(?R))*\)/', $matches['parts'], $partsMatches);

        return new self(
            $matches['subtype'],
            self::tryParseAttributes($matches['attributes']) ?? [],
            array_filter(array_map(
                fn (string $rawPart) => Part::tryParse($rawPart),
                $partsMatches[0]
            )),
        );
    }
}