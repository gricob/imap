<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data;

use Gricob\IMAP\Protocol\Response\Line\Data\Item\BodySectionItem;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructureItem;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\EnvelopeItem;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\FlagsItem;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\InternalDateItem;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\Rfc822Item;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\Rfc822SizeItem;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\UidItem;

final readonly class FetchData implements Data
{
    private const PATTERN = '/\* (?<id>\d*) FETCH (?<rawItems>.*)/ms';

    public function __construct(
        public int $id,
        public ?FlagsItem $flags,
        public ?InternalDateItem $internalDate,
        public ?EnvelopeItem $envelope,
        public ?Rfc822SizeItem $rfc822Size,
        public ?Rfc822Item $rfc822,
        public ?UidItem $uid,
        public ?BodyStructureItem $bodyStructure,
        public ?array $bodySections,
    ) {
    }

    public static function tryParse(string $raw): ?static
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        $rawItems = $matches['rawItems'];

        return new self(
            (int) $matches['id'],
            FlagsItem::tryParse($rawItems),
            InternalDateItem::tryParse($rawItems),
            EnvelopeItem::tryParse($rawItems),
            Rfc822SizeItem::tryParse($rawItems),
            Rfc822Item::tryParse($rawItems),
            UidItem::tryParse($rawItems),
            BodyStructureItem::tryParse($rawItems),
            BodySectionItem::tryParseAll($rawItems),
        );
    }

    public function getBodySection(string $name): ?BodySectionItem
    {
        foreach ($this->bodySections as $bodySection) {
            if ($bodySection->section == $name) {
                return $bodySection;
            }
        }

        return null;
    }
}