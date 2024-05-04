<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

use RuntimeException;

class UnexpectedContinuationHandler implements ContinuationHandler
{
    public function continue(): void
    {
        throw new RuntimeException('Unexpected continuation response');
    }
}