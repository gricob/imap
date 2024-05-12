<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final class ListData implements Data
{
    /**
     * @param list<string> $nameAttributes
     */
    public function __construct(
        public array $nameAttributes,
        public string $hierarchyDelimiter,
        public string $name
    ) {
    }
}