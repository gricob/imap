<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

final readonly class Rfc822Item
{
    private const PATTERN = '/RFC822 \{(?<bytes>\d+)}\r\n(?<content>.*)/ms';

    public function __construct(public string $content)
    {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self(substr($matches['content'], 0, (int) $matches['bytes']));
    }
}