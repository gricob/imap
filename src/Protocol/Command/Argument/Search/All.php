<?php

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

class All implements Criteria
{
    public function __toString(): string
    {
        return 'ALL';
    }
}