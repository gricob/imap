<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

final readonly class FlagsItem
{
    private const PATTERN = '/FLAGS \((?<flags>.*?)\)/';

    public function __construct(public array $flags)
    {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        if (empty($matches['flags'])) {
            return new self([]);
        }

        return new self(explode(' ', $matches['flags']));
    }
}