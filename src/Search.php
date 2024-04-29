<?php

namespace Gricob\IMAP;

use Gricob\IMAP\Mime\Message;
use Gricob\IMAP\Protocol\Command\Argument\Search\Before;
use Gricob\IMAP\Protocol\Command\Argument\Search\Since;

class Search
{
    private array $criteria;

    public function __construct(private readonly Client $client)
    {
    }

    public function before(\DateTimeInterface $date): self
    {
        $this->criteria[] = new Before($date);

        return $this;
    }

    public function since(\DateTimeInterface $date): self
    {
        $this->criteria[] = new Since($date);

        return $this;
    }

    /**
     * @return array<Message>
     */
    public function get(): array
    {
        return $this->client->doSearch(...$this->criteria);
    }
}