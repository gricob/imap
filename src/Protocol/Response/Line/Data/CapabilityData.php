<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class CapabilityData implements Data
{
    private const PATTERN = '/\* CAPABILITY (?<capabilities>.*)/';

    /**
     * @param list<string> $capabilities
     */
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