<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch;

use DateTimeImmutable;

final readonly class Envelope
{
    /**
     * @param Address[]|null $from
     * @param Address[]|null $sender
     * @param Address[]|null $replyTo
     * @param Address[]|null $to
     * @param Address[]|null $cc
     * @param Address[]|null $bcc
     */
    public function __construct(
        public ?DateTimeImmutable $date,
        public ?string $subject,
        public ?array $from,
        public ?array $sender,
        public ?array $replyTo,
        public ?array $to,
        public ?array $cc,
        public ?array $bcc,
        public ?string $inReplyTo,
        public ?string $messageId,
    ) {
    }
}