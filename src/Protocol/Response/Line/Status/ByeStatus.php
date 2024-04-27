<?php

namespace Gricob\IMAP\Protocol\Response\Line\Status;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc9051#name-bye-response
 */
final readonly class ByeStatus extends Status
{
    public static function status(): string
    {
        return 'BYE';
    }
}