<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

class Rfc822SizeItem
{
    private const PATTERN = '/RFC822.SIZE (?<size>\d*)/';

    public function __construct(public int $size)
    {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self($matches['size']);
    }
}