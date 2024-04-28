<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

class FlagsData implements Data
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