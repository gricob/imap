<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

interface ContinuationHandler
{
    public function continue(): void;
}