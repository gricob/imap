<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data;

use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodySection;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\Envelope;

final readonly class FetchData implements Data
{
    /**
     * @param array<string>|null $flags
     * @param BodySection[] $bodySections
     */
    public function __construct(
        public int $id,
        public ?array $flags = null,
        public ?\DateTimeImmutable $internalDate = null,
        public ?Envelope $envelope = null,
        public ?int $rfc822Size = null,
        public ?string $rfc822 = null,
        public ?int $uid = null,
        public ?BodyStructure $bodyStructure = null,
        public array $bodySections = [],
    ) {
    }

    public function getBodySection(string $name): ?BodySection
    {
        foreach (($this->bodySections ?? []) as $bodySection) {
            if ($bodySection->section == $name) {
                return $bodySection;
            }
        }

        return null;
    }
}