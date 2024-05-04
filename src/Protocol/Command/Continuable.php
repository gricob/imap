<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command;

interface Continuable
{
    public function continue(): string;
}