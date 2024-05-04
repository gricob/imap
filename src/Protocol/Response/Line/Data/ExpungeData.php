<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class ExpungeData implements Data
{
    private const PATTERN = '/^[*] (?<id>\d*) EXPUNGE/';

    public function __construct(public int $id)
    {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self((int) $matches['id']);
    }
}