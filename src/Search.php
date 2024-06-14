<?php

declare(strict_types=1);

namespace Gricob\IMAP;

use BadMethodCallException;
use DateTimeInterface;
use Gricob\IMAP\Mime\Message;
use Gricob\IMAP\Protocol\Command\Argument\Search\All;
use Gricob\IMAP\Protocol\Command\Argument\Search\Before;
use Gricob\IMAP\Protocol\Command\Argument\Search\Criteria;
use Gricob\IMAP\Protocol\Command\Argument\Search\Header;
use Gricob\IMAP\Protocol\Command\Argument\Search\Not;
use Gricob\IMAP\Protocol\Command\Argument\Search\Since;

class Search
{
    /**
     * @var list<Criteria>
     */
    private array $criteria;

    private bool $not = false;

    public function __construct(private readonly Client $client)
    {
    }

    public function header(string $fieldName, string $value = ''): self
    {
        $this->addCriteria(new Header($fieldName, $value));

       return $this;
    }

    public function before(DateTimeInterface $date): self
    {
        $this->addCriteria(new Before($date));

        return $this;
    }

    public function since(DateTimeInterface $date): self
    {
        $this->addCriteria(new Since($date));

        return $this;
    }

    public function not(): self
    {
        $this->not = true;

        return $this;
    }

    /**
     * @return array<Message>
     */
    public function get(): array
    {
        if ($this->not) {
            throw new BadMethodCallException('Not key requires to specify a search key to be applied');
        }

        $criteria = empty($this->criteria)
            ? [new All()]
            : $this->criteria;

        return $this->client->doSearch(...$criteria);
    }

    private function addCriteria(Criteria $criteria): void
    {
        if ($this->not) {
            $criteria = new Not($criteria);
            $this->not = false;
        }

        $this->criteria[] = $criteria;
    }
}