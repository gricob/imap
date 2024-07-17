<?php

declare(strict_types=1);

namespace Tests\Transport\Socket;

use Gricob\IMAP\Transport\ConnectionFailed;
use Gricob\IMAP\Transport\Socket\SocketConnection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SocketConnectionTest extends TestCase
{
    #[Test]
    public function openInvalidConnectionThrowsConnectionFailed(): void
    {
        $this->expectException(ConnectionFailed::class);

        $sut = new SocketConnection(
            'ssl',
            'localhost',
            3994,
            3,
        );

        $sut->open();
    }
}