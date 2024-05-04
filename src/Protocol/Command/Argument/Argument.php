<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Argument;

interface Argument
{
    public function __toString(): string;
}