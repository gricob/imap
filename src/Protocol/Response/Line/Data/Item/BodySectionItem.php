<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

final readonly class BodySectionItem
{
    private const PATTERN = '/BODY\[(?<section>.*?)] \{(?<bytes>\d+)}\r\n/s';

    public function __construct(public string $section, public string $text)
    {
    }

    public static function tryParseAll(string $raw): array
    {
        if (!preg_match_all(self::PATTERN, $raw, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $bodySections = [];
        foreach ($matches as $match) {
            $bodySections[] = new self(
                $match['section'][0],
                substr(
                    $raw,
                    $match['bytes'][1] + strlen($match['bytes'][0]) + 3,
                    (int) $match['bytes'][0]
                )
            );
        }

        return $bodySections;
    }


}