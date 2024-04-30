<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

final readonly class EnvelopeItemAddress
{
    private const PATTERN = '/(NIL|"(?<displayName>.*?)") ' .
        '(NIL|"(?<atDomainList>.*?)") ' .
        '(NIL|"(?<mailboxName>.*?)") ' .
        '(NIL|"(?<hostName>.*?)")/';

    public function __construct(
        public ?string $displayName,
        public ?string $atDomainList,
        public ?string $mailboxName,
        public ?string $hostName,
    ) {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self(
            $matches['displayName'] ?? null,
            $matches['atDomainList'] ?? null,
            $matches['mailboxName'] ?? null,
            $matches['hostName'] ?? null,
        );
    }

    /**
     * @return list<self>
     */
    public static function tryParseList(string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        return array_filter(array_map(
            fn (string $rawAddress) => self::tryParse($rawAddress),
            explode(') (', trim($raw, '()'))
        ));
    }
}