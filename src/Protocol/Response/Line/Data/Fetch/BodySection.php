<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch;

final readonly class BodySection
{
    public function __construct(public string $section, public string $text)
    {
    }
}