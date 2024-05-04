<?php

declare(strict_types=1);

namespace Gricob\IMAP\Mime\Part;

use Gricob\IMAP\Client;

class LazyBody extends Body
{
    public function __construct(
        private Client $client,
        private int $id,
        private string $section,
    ) {
    }

    public function __toString(): string
    {
        if (!isset($this->value)) {
            $this->value = $this->client->fetchSectionBody($this->id, $this->section);
        }

        return $this->value;
    }
}