<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class CapabilityData implements Data
{
    /**
     * @param list<string> $capabilities
     */
    public function __construct(public array $capabilities)
    {
    }
}