<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Response\Line\Status\Status;
use RuntimeException;

class CommandFailed extends RuntimeException
{
    public static function withStatus(Status $status): self
    {
        return new self($status->message);
    }
}