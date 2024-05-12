<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class FlagsData implements Data
{
    /**
     * @param list<string> $flags
     */
    public function __construct(public array $flags)
    {
    }
}