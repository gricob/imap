<?php

declare(strict_types=1);

namespace Gricob\IMAP\Mime\Part;

final readonly class Disposition
{
    public function __construct(
        public string $type,
        public ?string $filename,
    ) {
    }
}