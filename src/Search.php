<?php

declare(strict_types=1);

namespace Gricob\IMAP;

use DateTimeInterface;
use Gricob\IMAP\Mime\Message;
use Gricob\IMAP\Protocol\Command\Argument\Search\All;
use Gricob\IMAP\Protocol\Command\Argument\Search\Before;
use Gricob\IMAP\Protocol\Command\Argument\Search\Criteria;
use Gricob\IMAP\Protocol\Command\Argument\Search\Since;

class Search
{
    /**
     * @var list<Criteria>
     */
    private array $criteria;

    public function __construct(private readonly Client $client)
    {
    }

    public function before(DateTimeInterface $date): self
    {
        $this->criteria[] = new Before($date);

        return $this;
    }

    public function since(DateTimeInterface $date): self
    {
        $this->criteria[] = new Since($date);

        return $this;
    }

    /**
     * @return array<Message>
     */
    public function get(): array
    {
        $criteria = empty($this->criteria)
            ? [new All()]
            : $this->criteria;

        return $this->client->doSearch(...$criteria);
    }
}