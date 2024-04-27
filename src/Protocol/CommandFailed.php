<?php

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Response\Line\Status\Status;
use RuntimeException;

class CommandFailed extends RuntimeException
{
    public static function withStatus(Status $status): self
    {
        return new self(
            sprintf('%s %s %s', $status->tag, $status::status(), $status->message)
        );
    }
}