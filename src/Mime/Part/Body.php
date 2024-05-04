<?php

declare(strict_types=1);

namespace Gricob\IMAP\Mime\Part;

use Stringable;

class Body implements Stringable
{
    public function __construct(
        protected string $value
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}