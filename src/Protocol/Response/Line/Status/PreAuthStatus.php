<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Status;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc9051#name-preauth-response
 */
final readonly class PreAuthStatus extends Status
{
    public static function status(): string
    {
        return 'PREAUTH';
    }
}