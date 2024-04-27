<?php

namespace Gricob\IMAP;

final readonly class Configuration
{
    public function __construct(
        public string $transport,
        public string $host,
        public int $port = 993,
        public int $timeout = 60,
        public bool $verifyPeer = true,
        public bool $verifyPeerName = true,
        public bool $allowSelfSigned = false,
        public bool $useUid = true,
    ) {
    }
}