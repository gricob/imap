<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;

use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\Envelope;

readonly class MessagePart extends SinglePart
{
    /**
     * @param array<string, string> $attributes
     * @param string[]|null $language
     */
    public function __construct(
        array $attributes,
        ?string $id,
        ?string $description,
        string $encoding,
        int $size,
        public Envelope $envelope,
        public BodyStructure $bodyStructure,
        public int $textLines,
        ?string $md5,
        ?Disposition $disposition,
        ?array $language,
        ?string $location,
    ) {
        parent::__construct(
            'MESSAGE',
            'RFC822',
            $attributes,
            $id,
            $description,
            $encoding,
            $size,
            $md5,
            $disposition,
            $language,
            $location,
        );
    }
}