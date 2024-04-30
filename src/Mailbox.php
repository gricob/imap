<?php

namespace Gricob\IMAP;

readonly class Mailbox
{
    private const ATTRIBUTE_NOSELECT = '\Noselect';

    /**
     * @param list<string> $nameAttributes
     */
    public function __construct(
        public array $nameAttributes,
        public string $hierarchyDelimiter,
        public string $name,
    ) {
    }

    public function isSelectable(): bool
    {
        return !in_array(self::ATTRIBUTE_NOSELECT, $this->nameAttributes);
    }
}