<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class ExistsData implements Data
{
    public function __construct(public int $numberOfMessages)
    {
    }
}