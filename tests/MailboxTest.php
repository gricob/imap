<?php

declare(strict_types=1);

namespace Tests;

use Gricob\IMAP\Mailbox;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MailboxTest extends TestCase
{
    #[Test]
    #[DataProvider('isSelectableProvider')]
    public function isSelectable(Mailbox $mailbox, bool $expected)
    {
        $this->assertEquals($expected, $mailbox->isSelectable());
    }

    public static function isSelectableProvider(): array
    {
        return [
            'Without \Noselect attribute' => [new Mailbox([], '.', 'INBOX'), true],
            'With \Noselect attribute' => [new Mailbox(['\Noselect'], '.', 'INBOX'), false],
        ];
    }
}