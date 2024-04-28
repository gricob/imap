<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item;

final readonly class EnvelopeItem
{
    private const PATTERN = '/ENVELOPE \("(?<date>.*)" "(?<subject>.*)" (?<from>\(\(.*?\)\)) (?<sender>\(\(.*?\)\)) (?<replyTo>\(\(.*?\)\)) (NIL|(?<to>\(\(.*?\)\))) (NIL|(?<cc>\(\(.*?\)\))) (NIL|(?<bcc>\(\(.*?\)\))) (NIL|"(?<inReplyTo>.*?)") (NIL|"(?<messageId>.*?)")\)/';

    public function __construct(
        public \DateTimeImmutable $date,
        public string $subject,
        public EnvelopeItemAddress $from,
        public EnvelopeItemAddress $sender,
        public EnvelopeItemAddress $replyTo,
        public array $to,
        public array $cc,
        public array $bcc,
        public ?string $messageId,
    ) {
    }

    public static function tryParse(string $raw): ?self
    {
        if (!preg_match(self::PATTERN, $raw, $matches)) {
            return null;
        }

        return new self(
            new \DateTimeImmutable($matches['date']),
            $matches['subject'],
            EnvelopeItemAddress::tryParseList($matches['from'])[0],
            EnvelopeItemAddress::tryParseList($matches['sender'])[0],
            EnvelopeItemAddress::tryParseList($matches['replyTo'])[0],
            EnvelopeItemAddress::tryParseList($matches['to'] ?? null),
            EnvelopeItemAddress::tryParseList($matches['cc'] ?? null),
            EnvelopeItemAddress::tryParseList($matches['bcc'] ?? null),
            $matches['messageId'] ?? null,
        );
    }
}