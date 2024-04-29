<?php

namespace Gricob\IMAP\Mime\Part;

class Body implements \Stringable
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