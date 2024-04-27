<?php

namespace Tests;

use Gricob\IMAP\Client;
use Gricob\IMAP\Configuration;
use Gricob\IMAP\Mailbox;
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
    public function list()
    {
        $mailboxes = self::$sut->list();

        $this->assertEquals([new Mailbox([], '.', 'INBOX')], $mailboxes);
    }

    #[Test]
    #[Depends('logIn')]
    #[DoesNotPerformAssertions]
    public function mailbox()
    {
        self::$sut->select('INBOX');
    }

    #[Test]
    #[Depends('logIn')]
    public function append()
    {
        $message = <<<RFC822
        MIME-Version: 1.0
        Date: Sat, 27 Apr 2024 20:49:48 +0200
        Message-ID: <CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>
        Subject: Lorem ipsum
        From: Sender <sender@localhost>
        To: User <user@localhost>
        Content-Type: text/plain; charset="UTF-8"
        
        Dolor sit amet
        RFC822;

        $uid = self::$sut->append($message);

        $this->assertGreaterThanOrEqual(1, $uid);
    }

    #[Test]
    #[Depends('append')]
    public function search()
    {
        $result = self::$sut->search()
            ->since(new \DateTimeImmutable('yesterday'))
            ->before(new \DateTimeImmutable('tomorrow'))
            ->get();

        $this->assertContainsOnly('int', $result);
    }
}