<?php

namespace Gricob\IMAP\Mime;

use Gricob\IMAP\Client;
use Gricob\IMAP\Mime\Part\Part;

class LazyMessage extends Message
{
    public function __construct(
        private Client $client,
        int $id,
    ) {
        $this->id = $id;
    }

    public function headers(): array
    {
        if (!isset($this->headers)) {
            $this->headers = $this->client->fetchHeaders($this->id);
        }

        return parent::headers();
    }

    public function body(): Part
    {
        if (!isset($this->body)) {
            $this->body = $this->client->fetchBody($this->id);
        }

        return parent::body();
    }

    public function internalDate(): \DateTimeImmutable
    {
        if (!isset($this->internalDate)) {
            $this->internalDate = $this->client->fetchInternalDate($this->id);
        }

        return parent::internalDate();
    }
}