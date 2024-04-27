<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

use Gricob\IMAP\Protocol\Response\Line\Line;

class FlagsData implements Line
{
    private const PATTERN = '/^\* FLAGS \((?<flags>(\\\w* ?)*)/';

    public function __construct(array $flags)
    {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self(explode(' ', $matches['flags']));
    }
}