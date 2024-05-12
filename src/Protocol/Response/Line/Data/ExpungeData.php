<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class ExpungeData implements Data
{
    public function __construct(public int $id)
    {
    }
}