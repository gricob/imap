<?php

namespace Gricob\IMAP\Protocol\Response\Line\Status\Code;

interface Code
{
    public static function tryParse(string $raw): ?static;
}