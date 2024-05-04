<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Status;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc9051#name-bad-response
 */
final readonly class BadStatus extends Status
{
    public static function status(): string
    {
        return 'BAD';
    }
}