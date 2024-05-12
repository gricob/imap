<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

final readonly class SearchData implements Data
{
    /**
     * @param list<int> $numbers
     */
    public function __construct(public array $numbers)
    {
    }
}