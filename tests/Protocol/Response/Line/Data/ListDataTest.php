<?php

namespace Protocol\Response\Line\Data;

use Gricob\IMAP\Protocol\Response\Line\Data\ListData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ListDataTest extends TestCase
{
    #[Test]
    #[DataProvider('tryParseProvider')]
    public function tryParse(string $raw, ?ListData $expected)
    {
        $this->assertEquals($expected, ListData::tryParse($raw));
    }

    public static function tryParseProvider()
    {
        return [
            'no list raw' => ['* FETCH', null],
            'name with quotes' => ['* LIST () "/" "Mailbox name"', new ListData([], '/', 'Mailbox name')],
            'name without quotes' => ['* LIST () "/" Mailbox name', new ListData([], '/', 'Mailbox name')],
            'with name attributes' => ['* LIST (\Noselect \HasNoChildren) "/" Mailbox', new ListData(['\Noselect', '\HasNoChildren'], '/', 'Mailbox')],
        ];
    }
}