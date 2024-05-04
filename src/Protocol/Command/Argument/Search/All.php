<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

class All implements Criteria
{
    public function __toString(): string
    {
        return 'ALL';
    }
}