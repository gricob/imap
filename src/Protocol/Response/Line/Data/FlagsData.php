<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class FlagsData implements Data
{
    private const PATTERN = '/^\* FLAGS \((?<flags>(\\\w* ?)*)/';

    /**
     * @param list<string> $flags
     */
    public function __construct(public array $flags)
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