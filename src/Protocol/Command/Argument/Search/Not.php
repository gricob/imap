<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

final readonly class Not implements Criteria
{
    public function __construct(private Criteria $criteria)
    {
    }

    public function __toString(): string
    {
        return 'NOT ('.$this->criteria.')';
    }
}