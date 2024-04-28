<?php

namespace Gricob\IMAP;

use Gricob\IMAP\Protocol\Command\Argument\Search\Before;
use Gricob\IMAP\Protocol\Command\Argument\Search\Since;
use Gricob\IMAP\Protocol\Imap;
use Gricob\IMAP\Protocol\Response\Line\Data\SearchData;

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

    public function get(): array
    {
        $response = $this->client->send(
            new Protocol\Command\SearchCommand(
                $this->client->configuration->useUid,
                ...$this->criteria
            )
        );

        $result = [];
        foreach ($response->data as $data) {
            if ($data instanceof SearchData) {
                array_push($result, ...$data->numbers);
            }
        }

        return $result;
    }
}