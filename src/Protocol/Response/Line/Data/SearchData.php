<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final class SearchData implements Data
{
    private const PATTERN = '/^\* SEARCH( (?<numbers>\d*( ?\d*)+)?)?/';

    /**
     * @param list<int> $numbers
     */
    public function __construct(public readonly array $numbers)
    {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        if (!isset($matches['numbers'])) {
            return new self([]);
        }

        $numbers = explode(' ', $matches['numbers']);

        return new self(array_map('intval', $numbers));
    }
}