<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Status;

use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\Code;

final readonly class Status implements Line
{
    final public function __construct(
        public string $tag,
        public StatusType $type,
        public ?Code $code,
        public string $message
    ) {
    }
}