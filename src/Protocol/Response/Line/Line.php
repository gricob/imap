<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line;

interface Line
{
    public static function tryParse(string $raw): ?static;
}