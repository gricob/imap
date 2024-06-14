<?php

namespace Gricob\IMAP\Protocol\Command\Argument\Search;

use Gricob\IMAP\Protocol\Command\Argument\QuotedString;

class Header implements Criteria
{
    public function __construct(
        private string $fieldName,
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('HEADER %s %s', $this->fieldName, new QuotedString($this->value));
    }
}