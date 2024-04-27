<?php

namespace Gricob\IMAP\Protocol\Response\Line;

interface Line
{
    public static function tryParse(string $raw): ?static;
}