<?php

namespace Gricob\IMAP\Protocol\Response\Line;

final class CommandContinuation implements Line
{
    private const PATTERN = '/^\+ OK( (?<message>( |\w)*))?/';

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw)) {
            return null;
        }

        return new self();
    }
}