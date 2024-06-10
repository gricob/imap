<?php

namespace Tests\Protocol\Response\Parser;

use Gricob\IMAP\Protocol\Response\Line\CommandContinuation;
use Gricob\IMAP\Protocol\Response\Line\Data\CapabilityData;
use Gricob\IMAP\Protocol\Response\Line\Data\ExistsData;
use Gricob\IMAP\Protocol\Response\Line\Data\ExpungeData;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\Address;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodySection;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\Envelope;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\FlagsItem;
use Gricob\IMAP\Protocol\Response\Line\Data\FetchData;
use Gricob\IMAP\Protocol\Response\Line\Data\FlagsData;
use Gricob\IMAP\Protocol\Response\Line\Data\ListData;
use Gricob\IMAP\Protocol\Response\Line\Data\RecentData;
use Gricob\IMAP\Protocol\Response\Line\Data\SearchData;
use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\AppendUidCode;
use Gricob\IMAP\Protocol\Response\Line\Status\Status;
use Gricob\IMAP\Protocol\Response\Line\Status\StatusType;
use Gricob\IMAP\Protocol\Response\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private static Parser $sut;

    public static function setUpBeforeClass(): void
    {
        self::$sut = new Parser();
    }

    #[Test]
    #[DataProvider('parseProvider')]
    public function parse(string $raw, Line $expected): void
    {
        $actual = self::$sut->parse($raw);

        $this->assertEquals($expected, $actual);
    }

    public static function parseProvider(): array
    {
        return [
            '* OK' => ["* OK\r\n", new Status('*', StatusType::OK, null, '')],
            'A001 OK' => ["A001 OK\r\n", new Status('A001', StatusType::OK, null, '')],
            'A001 OK [APPENDUID 9999 10]' => [
                "A001 OK [APPENDUID 9999 10]\r\n",
                new Status('A001', StatusType::OK, new AppendUidCode(9999, 10), '')
            ],
            'A001 OK Lorem ipsum' => [
                "A001 OK Lorem ipsum\r\n",
                new Status('A001', StatusType::OK, null, 'Lorem ipsum')
            ],
            'A001 NO' => ["A001 NO\r\n", new Status('A001', StatusType::NO, null, '')],
            '* CAPABILITY IMAP4rev1 STARTTLS AUTH=GSSAPI XPIG-LATIN' => [
                "* CAPABILITY IMAP4rev1 STARTTLS AUTH=GSSAPI XPIG-LATIN\r\n",
                new CapabilityData(['IMAP4rev1', 'STARTTLS', 'AUTH=GSSAPI', 'XPIG-LATIN'])
            ],
            '* LIST () "/" ~/Mail/foo' => ["* LIST () \"/\" ~/Mail/foo\r\n", new ListData([], '/', '~/Mail/foo')],
            '* LIST (\Noselect) "/" ~/Mail/foo' => [
                "* LIST (\Noselect) \"/\" ~/Mail/foo\r\n",
                new ListData(['\Noselect'], '/', '~/Mail/foo')
            ],
            '* LIST (\HasNoChildren) "/" "INBOX"' => [
                "* LIST (\HasNoChildren) \"/\" \"INBOX\"\r\n",
                new ListData(['\HasNoChildren'], '/', 'INBOX')
            ],
            '* LIST (\Noselect \Noinferiors) "/" ~/Mail/foo' => [
                "* LIST (\Noselect \Noinferiors) \"/\" ~/Mail/foo\r\n",
                new ListData(['\Noselect', '\Noinferiors'], '/', '~/Mail/foo')
            ],
            '* LIST () "/" {5}"foo"' => ["* LIST () \"/\" {5}\r\n\"foo\"\r\n", new ListData([], '/', '"foo"')],
            '* 23 EXISTS' => ["* 23 EXISTS\r\n", new ExistsData(23)],
            '* 52 EXPUNGE' => ["* 52 EXPUNGE\r\n", new ExpungeData(52)],
            '* FLAGS ()' => ["* FLAGS ()\r\n", new FlagsData([])],
            '* FLAGS (\Answered \Flagged \Deleted \Seen \Draft)' => [
                "* FLAGS (\Answered \Flagged \Deleted \Seen \Draft)\r\n",
                new FlagsData(['\Answered', '\Flagged', '\Deleted', '\Seen', '\Draft'])
            ],
            '* 5 RECENT' => ["* 5 RECENT\r\n", new RecentData(5)],
            '* SEARCH 2 3 6' => ["* SEARCH 2 3 6\r\n", new SearchData([2, 3, 6])],
            '* 23 FETCH (FLAGS (\Seen))' => ["* 23 FETCH (FLAGS (\Seen))\r\n", new FetchData(23, ['\Seen'])],
            '* 23 FETCH (INTERNALDATE "17-Jul-1996 02:44:25 -0700")' => [
                "* 23 FETCH (INTERNALDATE \"17-Jul-1996 02:44:25 -0700\")\r\n",
                new FetchData(23, internalDate: new \DateTimeImmutable("17-Jul-1996 02:44:25 -0700"))
            ],
            '* 23 FETCH (UID 17)' => ["* 23 FETCH (UID 17)\r\n", new FetchData(23, uid: 17)],
            '* 23 FETCH (RFC822.SIZE 44827)' => [
                "* 23 FETCH (RFC822.SIZE 44827)\r\n",
                new FetchData(23, rfc822Size: 44827)
            ],
            '* 12 FETCH (ENVELOPE ...)' => [
                "* 12 FETCH (ENVELOPE (\"Wed, 17 Jul 1996 02:23:25 -0700 (PDT)\"" .
                " \"IMAP4rev1 WG mtg summary and minutes\"" .
                " ((\"Terry Gray\" NIL \"gray\" \"cac.washington.edu\"))" .
                " ((\"Terry Gray\" NIL \"gray\" \"cac.washington.edu\"))" .
                " ((\"Terry Gray\" NIL \"gray\" \"cac.washington.edu\"))" .
                " ((NIL NIL \"imap\" \"cac.washington.edu\"))" .
                " ((NIL NIL \"minutes\" \"CNRI.Reston.VA.US\")(\"John Klensin\" NIL \"KLENSIN\" \"MIT.EDU\"))" .
                " ((NIL NIL \"bcc\" \"cac.washington.edu\"))" .
                " \"<A35975-0200000@cac.washington.edu>\"" .
                " \"<B27397-0100000@cac.washington.edu>\"))\r\n",
                new FetchData(
                    12, envelope: new Envelope(
                    new \DateTimeImmutable('Wed, 17 Jul 1996 02:23:25 -0700 (PDT)'),
                    'IMAP4rev1 WG mtg summary and minutes',
                    [new Address('Terry Gray', null, 'gray', 'cac.washington.edu')],
                    [new Address('Terry Gray', null, 'gray', 'cac.washington.edu')],
                    [new Address('Terry Gray', null, 'gray', 'cac.washington.edu')],
                    [new Address(null, null, 'imap', 'cac.washington.edu')],
                    [
                        new Address(null, null, 'minutes', 'CNRI.Reston.VA.US'),
                        new Address('John Klensin', null, 'KLENSIN', 'MIT.EDU'),
                    ],
                    [new Address(null, null, 'bcc', 'cac.washington.edu')],
                    '<A35975-0200000@cac.washington.edu>',
                    '<B27397-0100000@cac.washington.edu>'
                )
                )
            ],
            '* 12 FETCH (ENVELOPE NIL...)' => [
                "* 12 FETCH (ENVELOPE (NIL NIL NIL NIL NIL NIL NIL NIL NIL NIL))\r\n",
                new FetchData(
                    12, envelope: new Envelope(
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                )
                )
            ],
            '* 12 FETCH (BODYSTRUCTURE ("TEXT" ...)' => [
                "* 12 FETCH (BODYSTRUCTURE (\"TEXT\" \"PLAIN\" (\"CHARSET\" \"US-ASCII\") NIL NIL \"7BIT\" 2279 48))\r\n",
                new FetchData(
                    12,
                    bodyStructure: new BodyStructure(
                        new BodyStructure\TextPart(
                            'PLAIN',
                            [
                                'CHARSET' => 'US-ASCII',
                            ],
                            null,
                            null,
                            '7BIT',
                            2279,
                            48,
                            null,
                            null,
                            null,
                            null,
                        )
                    )
                )
            ],
            '* 12 FETCH (BODYSTRUCTURE ((...) MIXED ...))' => [
                "* 12 FETCH (BODYSTRUCTURE (" .
                "(\"TEXT\" \"PLAIN\" (\"CHARSET\" \"US-ASCII\") NIL NIL \"7BIT\" 1152 23)" .
                "(\"TEXT\" \"PLAIN\" (\"CHARSET\" \"US-ASCII\" \"NAME\" \"cc.diff\") " .
                "\"<960723163407.20117h@cac.washington.edu>\" \"Compiler diff\" \"BASE64\" 4554 73) \"MIXED\"))\r\n",
                new FetchData(
                    12,
                    bodyStructure: new BodyStructure(
                        new BodyStructure\MultiPart(
                            'MIXED',
                            [],
                            [
                                new BodyStructure\TextPart(
                                    'PLAIN',
                                    [
                                        'CHARSET' => 'US-ASCII',
                                    ],
                                    null,
                                    null,
                                    '7BIT',
                                    1152,
                                    23,
                                    null,
                                    null,
                                    null,
                                    null,
                                ),
                                new BodyStructure\TextPart(
                                    'PLAIN',
                                    [
                                        'CHARSET' => 'US-ASCII',
                                        'NAME' => 'cc.diff',
                                    ],
                                    '<960723163407.20117h@cac.washington.edu>',
                                    'Compiler diff',
                                    'BASE64',
                                    4554,
                                    73,
                                    null,
                                    null,
                                    null,
                                    null,
                                )
                            ],
                            null,
                            null,
                            null,
                        )
                    )
                )
            ],
            '* 12 FETCH (BODY[HEADER] ...)' => [
                "* 12 FETCH (BODY[HEADER] {342}\r\n" .
                "Date: Wed, 17 Jul 1996 02:23:25 -0700 (PDT)\r\n" .
                "From: Terry Gray <gray@cac.washington.edu>\r\n" .
                "Subject: IMAP4rev1 WG mtg summary and minutes\r\n" .
                "To: imap@cac.washington.edu\r\n" .
                "cc: minutes@CNRI.Reston.VA.US, John Klensin <KLENSIN@MIT.EDU>\r\n" .
                "Message-Id: <B27397-0100000@cac.washington.edu>\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: TEXT/PLAIN; CHARSET=US-ASCII\r\n" .
                "\r\n" .
                ")\r\n",
                new FetchData(
                    12,
                    bodySections: [
                        new BodySection(
                            'HEADER',
                            "Date: Wed, 17 Jul 1996 02:23:25 -0700 (PDT)\r\n" .
                            "From: Terry Gray <gray@cac.washington.edu>\r\n" .
                            "Subject: IMAP4rev1 WG mtg summary and minutes\r\n" .
                            "To: imap@cac.washington.edu\r\n" .
                            "cc: minutes@CNRI.Reston.VA.US, John Klensin <KLENSIN@MIT.EDU>\r\n" .
                            "Message-Id: <B27397-0100000@cac.washington.edu>\r\n" .
                            "MIME-Version: 1.0\r\n" .
                            "Content-Type: TEXT/PLAIN; CHARSET=US-ASCII\r\n" .
                            "\r\n"
                        ),
                    ]
                )
            ],
            '* 17 FETCH (BODYSTRUCTURE text without disposition attributes)' => [
                "* 17 FETCH (" .
                    "BODYSTRUCTURE (\"TEXT\" \"PLAIN\" (\"charset\" \"UTF-8\") NIL NIL \"7BIT\" 24 1 NIL (\"inline\" NIL) NIL)" .
                ")",
                new FetchData(
                    17,
                    bodyStructure: new BodyStructure(
                        new BodyStructure\TextPart(
                            'PLAIN',
                            ['charset' => 'UTF-8'],
                            null,
                            null,
                            '7BIT',
                            24,
                            1,
                            null,
                            new BodyStructure\Disposition('inline', []),
                            null,
                            null,
                        )
                    )
                )
            ],
            '+ Ready for additional command text' => [
                "+ Ready for additional command text\r\n",
                new CommandContinuation('Ready for additional command text')
            ],
        ];
    }
}