<?php

namespace Gricob\IMAP\Protocol\Response;

use Gricob\IMAP\Protocol\Response\Line\Status\Status;

final readonly class Response
{
    public function __construct(
        public Status $status,
        public array $data,
    ) {
    }
}