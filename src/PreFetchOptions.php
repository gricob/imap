<?php

declare(strict_types=1);

namespace Gricob\IMAP;

final readonly class PreFetchOptions
{
    public function __construct(
        public bool $internalDate = false,
        public bool $headers = false,
    ) {
    }
}