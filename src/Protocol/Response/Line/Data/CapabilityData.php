<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

use Gricob\IMAP\Protocol\Response\Line\Line;

final readonly class CapabilityData implements Line
{
    private const PATTERN = '/\* CAPABILITY (?<capabilities>.*)/';

    public function __construct(public array $capabilities)
    {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self(explode(' ', $matches['capabilities']));
    }
}