<?php

namespace Tests;

use Gricob\IMAP\Client;
use Gricob\IMAP\Configuration;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private static Client $sut;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $configuration = new Configuration(
            'ssl',
            'localhost',
            3993,
            verifyPeerName: false,
            allowSelfSigned: true,
        );

        self::$sut = Client::create($configuration);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function logIn()
    {
        self::$sut->logIn('user', 'pass');
    }

    #[Test]
    #[Depends('logIn')]
    #[DoesNotPerformAssertions]
    public function mailbox()
    {
        self::$sut->select('INBOX');
    }
}