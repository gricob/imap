<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Response\Line\Data\Fetch;

final readonly class Address
{
    public function __construct(
        public ?string $displayName,
        public ?string $atDomainList,
        public ?string $mailboxName,
        public ?string $hostName,
    ) {
    }
}