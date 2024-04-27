<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

use Gricob\IMAP\Protocol\Response\Line\Line;

class RecentData implements Line
{
    private const PATTERN = '/^[*] (?<numberOfMessages>\d*) RECENT/';

    public function __construct(public int $numberOfMessages)
    {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self((int) $matches['numberOfMessages']);
    }
}