<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

use Gricob\IMAP\Protocol\Command\Argument\Date;

readonly class Before extends Date implements Criteria
{
    public function __toString(): string
    {
        return 'BEFORE '.parent::__toString();
    }
}