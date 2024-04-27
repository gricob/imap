<?php

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

use Gricob\IMAP\Protocol\Command\Argument\Date;

readonly class Since extends Date implements Criteria
{
    public function __toString(): string
    {
        return 'SINCE '.parent::__toString();
    }
}