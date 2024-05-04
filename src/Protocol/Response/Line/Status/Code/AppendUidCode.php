<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Status\Code;

final readonly class AppendUidCode implements Code
{
    private const PATTERN = '/^APPENDUID (?<uidvalidity>\d*) (?<uid>\d*)/';

    public function __construct(
        public int $uidValidity,
        public int $uid,
    ) {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self((int) $matches['uidvalidity'], (int) $matches['uid']);
    }
}