<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

final readonly class InternalDateItem
{
    private const PATTERN = '/INTERNALDATE \"(?<date>\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} \+\d{4})\"/';

    public function __construct(public \DateTimeImmutable $date)
    {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        if (false === $date = \DateTimeImmutable::createFromFormat('d-M-Y H:i:s O', $matches['date'])) {
            return null;
        }

        return new self($date);
    }
}