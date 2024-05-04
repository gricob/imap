<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Status;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc9051#name-no-response
 */
final readonly class NoStatus extends Status
{
    public static function status(): string
    {
        return 'NO';
    }
}