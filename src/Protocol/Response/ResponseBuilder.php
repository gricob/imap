<?php

namespace Gricob\IMAP\Protocol\Response;

use Gricob\IMAP\Protocol\Response\Line\Data\Data;
use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\Status;

class ResponseBuilder
{
    private ?Status $status = null;

    /**
     * @var list<Line>
     */
    private array $data = [];

    public function __construct(private string $statusTag)
    {
    }

    public function addLine(Line $line): void
    {
        if ($line instanceof Status && $line->tag === $this->statusTag) {
            $this->status = $line;
            return;
        }

        $this->data[] = $line;
    }

    public function hasStatus(): bool
    {
        return $this->status !== null;
    }

    public function build(): Response
    {
        if (null === $this->status) {
            throw new \BadMethodCallException();
        }

        return new Response(
            $this->status,
            $this->data,
        );
    }
}