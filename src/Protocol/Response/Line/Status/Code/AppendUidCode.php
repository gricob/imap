<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Status\Code;

final readonly class AppendUidCode implements Code
{
    public function __construct(
        public int $uidValidity,
        public int $uid,
    ) {
    }
}