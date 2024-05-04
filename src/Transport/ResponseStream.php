<?php

declare(strict_types=1);

namespace Gricob\IMAP\Transport;

interface ResponseStream
{
    public function read(int $bytes): string;

    public function readLine(): string;
}