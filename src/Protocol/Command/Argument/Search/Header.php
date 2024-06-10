<?php

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

class Header implements Criteria
{
    public function __construct(
        private string $fieldName,
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('HEADER %s %s', $this->fieldName, $this->value);
    }
}