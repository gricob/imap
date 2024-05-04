<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response;

use Gricob\IMAP\Protocol\Response\Line\Data\Data;
use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\Status;

final readonly class Response
{
    /**
     * @param list<Line> $data
     */
    public function __construct(
        public Status $status,
        public array $data,
    ) {
    }

    /**
     * @template T of Line
     * @param class-string<T> $type
     * @return list<T>
     */
    public function getData(string $type): array
    {
        $result = [];
        foreach ($this->data as $data) {
            if ($data instanceof $type) {
                $result[] = $data;
            }
        }

        return $result;
    }
}