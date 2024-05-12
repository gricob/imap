<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch;

use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure\Part;

class BodyStructure
{
    public function __construct(
        public Part $part,
    ) {
    }
}