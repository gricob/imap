<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use Gricob\IMAP\Client;
use Gricob\IMAP\Configuration;
use Gricob\IMAP\Mailbox;
use Gricob\IMAP\MessageNotFound;
use Gricob\IMAP\Mime\LazyMessage;
use Gricob\IMAP\Mime\Part\MultiPart;
use Gricob\IMAP\Mime\Part\SinglePart;
use Gricob\IMAP\Protocol\Command\Authenticate\XOAuth2;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private static Configuration $configuration;
    private static Client $sut;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$configuration = new Configuration(
            'ssl',
            'localhost',
            3993,
            verifyPeerName: false,
            allowSelfSigned: true,
        );

        self::$sut = Client::create(self::$configuration);
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

        $this->assertContainsOnlyInstancesOf(Mailbox::class, $mailboxes);
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

        $uid = self::$sut->append($message, 'INBOX', [], new DateTimeImmutable());

        $this->assertGreaterThanOrEqual(1, $uid);
    }

    #[Test]
    #[Depends('append')]
    public function search()
    {
        $result = self::$sut->search()
            ->since(new DateTimeImmutable('yesterday'))
            ->before(new DateTimeImmutable('tomorrow'))
            ->get();

        $this->assertContainsOnly(LazyMessage::class, $result);
    }

    #[Test]
    #[Depends('mailbox')]
    public function fetchPlain()
    {
        $uid = self::$sut->append(
            <<<RFC822
            MIME-Version: 1.0
            Date: Sat, 27 Apr 2024 20:49:48 +0200
            Message-ID: <CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>
            User-Agent: Mozilla/5.0 (Iâ?; CPU iPhone OS 5_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML^C like Gecko) Version
            Subject: Lorem ipsum
            From: Sender <sender@localhost>
            To: User <user@localhost>
            Content-Type: text/plain; charset="UTF-8"
            
            Dolor sit amet
            RFC822
        );

        $message = self::$sut->fetch($uid);

        $this->assertEquals([
            'MIME-Version' => '1.0',
            'Date' => 'Sat, 27 Apr 2024 20:49:48 +0200',
            'Message-ID' => '<CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>',
            'User-Agent' => 'Mozilla/5.0 (I?; CPU iPhone OS 5_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML^C like Gecko) Version',
            'Subject' => 'Lorem ipsum',
            'From' => 'Sender <sender@localhost>',
            'To' => 'User <user@localhost>',
            'Content-Type' => 'text/plain; charset="UTF-8"'
        ], $message->headers());
        $this->assertInstanceOf(SinglePart::class, $message->body());
        $this->assertEquals('Dolor sit amet', $message->textBody());
        $this->assertNull($message->htmlBody());
    }

    #[Test]
    #[Depends('mailbox')]
    public function fetchAlternative()
    {
        $uid = self::$sut->append(
            <<<RFC822
            MIME-Version: 1.0
            Date: Sat, 27 Apr 2024 20:49:48 +0200
            Message-ID: <CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>
            Subject: Lorem ipsum
            From: Sender <sender@localhost>
            To: User <user@localhost>
            Content-Type: multipart/alternative; boundary="000000000000b6a7da061729c998"
            
            --000000000000b6a7da061729c998
            Content-Type: text/plain; charset="UTF-8"
            
            Dolor sit amet
            
            --000000000000b6a7da061729c998
            Content-Type: text/html; charset="UTF-8"
            
            <div dir="ltr">Dolor sit amet</div>
            
            --000000000000b6a7da061729c998--
            RFC822
        );

        $message = self::$sut->fetch($uid);

        $this->assertEquals([
            'MIME-Version' => '1.0',
            'Date' => 'Sat, 27 Apr 2024 20:49:48 +0200',
            'Message-ID' => '<CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>',
            'Subject' => 'Lorem ipsum',
            'From' => 'Sender <sender@localhost>',
            'To' => 'User <user@localhost>',
            'Content-Type' => 'multipart/alternative; boundary="000000000000b6a7da061729c998"'
        ], $message->headers());
        $this->assertInstanceOf(MultiPart::class, $message->body());
        $this->assertEquals('alternative', $message->body()->subtype);
        $this->assertEquals('Dolor sit amet', $message->textBody());
        $this->assertEquals('<div dir="ltr">Dolor sit amet</div>', $message->htmlBody());
    }

    #[Test]
    #[Depends('mailbox')]
    public function fetchMixed()
    {
        $attachment = <<<BASE64
        /9j/4QDKRXhpZgAATU0AKgAAAAgABgESAAMAAAABAAEAAAEaAAUAAAABAAAAVgEbAAUAAAABAAAA
        XgEoAAMAAAABAAIAAAITAAMAAAABAAEAAIdpAAQAAAABAAAAZgAAAAAAAAAeAAAAAQAAAB4AAAAB
        AAeQAAAHAAAABDAyMjGRAQAHAAAABAECAwCgAAAHAAAABDAxMDCgAQADAAAAAQABAACgAgAEAAAA
        AQAAAP2gAwAEAAAAAQAAADKkBgADAAAAAQAAAAAAAAAAAAD/4g/QSUNDX1BST0ZJTEUAAQEAAA/A
        YXBwbAIQAABtbnRyUkdCIFhZWiAH6AABAAEADgARAANhY3NwQVBQTAAAAABBUFBMAAAAAAAAAAAA
        AAAAAAAAAAAA9tYAAQAAAADTLWFwcGwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
        AAAAAAAAAAAAAAAAABFkZXNjAAABUAAAAGJkc2NtAAABtAAABJxjcHJ0AAAGUAAAACN3dHB0AAAG
        dAAAABRyWFlaAAAGiAAAABRnWFlaAAAGnAAAABRiWFlaAAAGsAAAABRyVFJDAAAGxAAACAxhYXJn
        AAAO0AAAACB2Y2d0AAAO8AAAADBuZGluAAAPIAAAAD5tbW9kAAAPYAAAACh2Y2dwAAAPiAAAADhi
        VFJDAAAGxAAACAxnVFJDAAAGxAAACAxhYWJnAAAO0AAAACBhYWdnAAAO0AAAACBkZXNjAAAAAAAA
        AAhEaXNwbGF5AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
        AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAbWx1YwAAAAAAAAAmAAAADGhySFIAAAAU
        AAAB2GtvS1IAAAAMAAAB7G5iTk8AAAASAAAB+GlkAAAAAAASAAACCmh1SFUAAAAUAAACHGNzQ1oA
        AAAWAAACMGRhREsAAAAcAAACRm5sTkwAAAAWAAACYmZpRkkAAAAQAAACeGl0SVQAAAAYAAACiGVz
        RVMAAAAWAAACoHJvUk8AAAASAAACtmZyQ0EAAAAWAAACyGFyAAAAAAAUAAAC3nVrVUEAAAAcAAAC
        8mhlSUwAAAAWAAADDnpoVFcAAAAKAAADJHZpVk4AAAAOAAADLnNrU0sAAAAWAAADPHpoQ04AAAAK
        AAADJHJ1UlUAAAAkAAADUmVuR0IAAAAUAAADdmZyRlIAAAAWAAADim1zAAAAAAASAAADoGhpSU4A
        AAASAAADsnRoVEgAAAAMAAADxGNhRVMAAAAYAAAD0GVuQVUAAAAUAAADdmVzWEwAAAASAAACtmRl
        REUAAAAQAAAD6GVuVVMAAAASAAAD+HB0QlIAAAAYAAAECnBsUEwAAAASAAAEImVsR1IAAAAiAAAE
        NHN2U0UAAAAQAAAEVnRyVFIAAAAUAAAEZnB0UFQAAAAWAAAEemphSlAAAAAMAAAEkABMAEMARAAg
        AHUAIABiAG8AagBpzuy37AAgAEwAQwBEAEYAYQByAGcAZQAtAEwAQwBEAEwAQwBEACAAVwBhAHIA
        bgBhAFMAegDtAG4AZQBzACAATABDAEQAQgBhAHIAZQB2AG4A/QAgAEwAQwBEAEwAQwBEAC0AZgBh
        AHIAdgBlAHMAawDmAHIAbQBLAGwAZQB1AHIAZQBuAC0ATABDAEQAVgDkAHIAaQAtAEwAQwBEAEwA
        QwBEACAAYQAgAGMAbwBsAG8AcgBpAEwAQwBEACAAYQAgAGMAbwBsAG8AcgBMAEMARAAgAGMAbwBs
        AG8AcgBBAEMATAAgAGMAbwB1AGwAZQB1AHIgDwBMAEMARAAgBkUGRAZIBkYGKQQaBD4EOwRMBD4E
        QAQ+BDIEOAQ5ACAATABDAEQgDwBMAEMARAAgBeYF0QXiBdUF4AXZX2mCcgBMAEMARABMAEMARAAg
        AE0A4AB1AEYAYQByAGUAYgBuAP0AIABMAEMARAQmBDIENQRCBD0EPgQ5ACAEFgQaAC0ENAQ4BEEE
        PwQ7BDUEOQBDAG8AbABvAHUAcgAgAEwAQwBEAEwAQwBEACAAYwBvAHUAbABlAHUAcgBXAGEAcgBu
        AGEAIABMAEMARAkwCQIJFwlACSgAIABMAEMARABMAEMARAAgDioONQBMAEMARAAgAGUAbgAgAGMA
        bwBsAG8AcgBGAGEAcgBiAC0ATABDAEQAQwBvAGwAbwByACAATABDAEQATABDAEQAIABDAG8AbABv
        AHIAaQBkAG8ASwBvAGwAbwByACAATABDAEQDiAOzA8cDwQPJA7wDtwAgA78DuAPMA70DtwAgAEwA
        QwBEAEYA5AByAGcALQBMAEMARABSAGUAbgBrAGwAaQAgAEwAQwBEAEwAQwBEACAAYQAgAGMAbwBy
        AGUAczCrMOkw/ABMAEMARHRleHQAAAAAQ29weXJpZ2h0IEFwcGxlIEluYy4sIDIwMjQAAFhZWiAA
        AAAAAADzUQABAAAAARbMWFlaIAAAAAAAAIPfAAA9v////7tYWVogAAAAAAAASr8AALE3AAAKuVhZ
        WiAAAAAAAAAoOAAAEQsAAMi5Y3VydgAAAAAAAAQAAAAABQAKAA8AFAAZAB4AIwAoAC0AMgA2ADsA
        QABFAEoATwBUAFkAXgBjAGgAbQByAHcAfACBAIYAiwCQAJUAmgCfAKMAqACtALIAtwC8AMEAxgDL
        ANAA1QDbAOAA5QDrAPAA9gD7AQEBBwENARMBGQEfASUBKwEyATgBPgFFAUwBUgFZAWABZwFuAXUB
        fAGDAYsBkgGaAaEBqQGxAbkBwQHJAdEB2QHhAekB8gH6AgMCDAIUAh0CJgIvAjgCQQJLAlQCXQJn
        AnECegKEAo4CmAKiAqwCtgLBAssC1QLgAusC9QMAAwsDFgMhAy0DOANDA08DWgNmA3IDfgOKA5YD
        ogOuA7oDxwPTA+AD7AP5BAYEEwQgBC0EOwRIBFUEYwRxBH4EjASaBKgEtgTEBNME4QTwBP4FDQUc
        BSsFOgVJBVgFZwV3BYYFlgWmBbUFxQXVBeUF9gYGBhYGJwY3BkgGWQZqBnsGjAadBq8GwAbRBuMG
        9QcHBxkHKwc9B08HYQd0B4YHmQesB78H0gflB/gICwgfCDIIRghaCG4IggiWCKoIvgjSCOcI+wkQ
        CSUJOglPCWQJeQmPCaQJugnPCeUJ+woRCicKPQpUCmoKgQqYCq4KxQrcCvMLCwsiCzkLUQtpC4AL
        mAuwC8gL4Qv5DBIMKgxDDFwMdQyODKcMwAzZDPMNDQ0mDUANWg10DY4NqQ3DDd4N+A4TDi4OSQ5k
        Dn8Omw62DtIO7g8JDyUPQQ9eD3oPlg+zD88P7BAJECYQQxBhEH4QmxC5ENcQ9RETETERTxFtEYwR
        qhHJEegSBxImEkUSZBKEEqMSwxLjEwMTIxNDE2MTgxOkE8UT5RQGFCcUSRRqFIsUrRTOFPAVEhU0
        FVYVeBWbFb0V4BYDFiYWSRZsFo8WshbWFvoXHRdBF2UXiReuF9IX9xgbGEAYZRiKGK8Y1Rj6GSAZ
        RRlrGZEZtxndGgQaKhpRGncanhrFGuwbFBs7G2MbihuyG9ocAhwqHFIcexyjHMwc9R0eHUcdcB2Z
        HcMd7B4WHkAeah6UHr4e6R8THz4faR+UH78f6iAVIEEgbCCYIMQg8CEcIUghdSGhIc4h+yInIlUi
        giKvIt0jCiM4I2YjlCPCI/AkHyRNJHwkqyTaJQklOCVoJZclxyX3JicmVyaHJrcm6CcYJ0kneier
        J9woDSg/KHEooijUKQYpOClrKZ0p0CoCKjUqaCqbKs8rAis2K2krnSvRLAUsOSxuLKIs1y0MLUEt
        di2rLeEuFi5MLoIuty7uLyQvWi+RL8cv/jA1MGwwpDDbMRIxSjGCMbox8jIqMmMymzLUMw0zRjN/
        M7gz8TQrNGU0njTYNRM1TTWHNcI1/TY3NnI2rjbpNyQ3YDecN9c4FDhQOIw4yDkFOUI5fzm8Ofk6
        Njp0OrI67zstO2s7qjvoPCc8ZTykPOM9Ij1hPaE94D4gPmA+oD7gPyE/YT+iP+JAI0BkQKZA50Ep
        QWpBrEHuQjBCckK1QvdDOkN9Q8BEA0RHRIpEzkUSRVVFmkXeRiJGZ0arRvBHNUd7R8BIBUhLSJFI
        10kdSWNJqUnwSjdKfUrESwxLU0uaS+JMKkxyTLpNAk1KTZNN3E4lTm5Ot08AT0lPk0/dUCdQcVC7
        UQZRUFGbUeZSMVJ8UsdTE1NfU6pT9lRCVI9U21UoVXVVwlYPVlxWqVb3V0RXklfgWC9YfVjLWRpZ
        aVm4WgdaVlqmWvVbRVuVW+VcNVyGXNZdJ114XcleGl5sXr1fD19hX7NgBWBXYKpg/GFPYaJh9WJJ
        Ypxi8GNDY5dj62RAZJRk6WU9ZZJl52Y9ZpJm6Gc9Z5Nn6Wg/aJZo7GlDaZpp8WpIap9q92tPa6dr
        /2xXbK9tCG1gbbluEm5rbsRvHm94b9FwK3CGcOBxOnGVcfByS3KmcwFzXXO4dBR0cHTMdSh1hXXh
        dj52m3b4d1Z3s3gReG54zHkqeYl553pGeqV7BHtje8J8IXyBfOF9QX2hfgF+Yn7CfyN/hH/lgEeA
        qIEKgWuBzYIwgpKC9INXg7qEHYSAhOOFR4Wrhg6GcobXhzuHn4gEiGmIzokziZmJ/opkisqLMIuW
        i/yMY4zKjTGNmI3/jmaOzo82j56QBpBukNaRP5GokhGSepLjk02TtpQglIqU9JVflcmWNJaflwqX
        dZfgmEyYuJkkmZCZ/JpomtWbQpuvnByciZz3nWSd0p5Anq6fHZ+Ln/qgaaDYoUehtqImopajBqN2
        o+akVqTHpTilqaYapoum/adup+CoUqjEqTepqaocqo+rAqt1q+msXKzQrUStuK4trqGvFq+LsACw
        dbDqsWCx1rJLssKzOLOutCW0nLUTtYq2AbZ5tvC3aLfguFm40blKucK6O7q1uy67p7whvJu9Fb2P
        vgq+hL7/v3q/9cBwwOzBZ8Hjwl/C28NYw9TEUcTOxUvFyMZGxsPHQce/yD3IvMk6ybnKOMq3yzbL
        tsw1zLXNNc21zjbOts83z7jQOdC60TzRvtI/0sHTRNPG1EnUy9VO1dHWVdbY11zX4Nhk2OjZbNnx
        2nba+9uA3AXcit0Q3ZbeHN6i3ynfr+A24L3hROHM4lPi2+Nj4+vkc+T85YTmDeaW5x/nqegy6Lzp
        RunQ6lvq5etw6/vshu0R7ZzuKO6070DvzPBY8OXxcvH/8ozzGfOn9DT0wvVQ9d72bfb794r4Gfio
        +Tj5x/pX+uf7d/wH/Jj9Kf26/kv+3P9t//9wYXJhAAAAAAADAAAAAmZmAADypwAADVkAABPQAAAK
        W3ZjZ3QAAAAAAAAAAQABAAAAAAAAAAEAAAABAAAAAAAAAAEAAAABAAAAAAAAAAEAAG5kaW4AAAAA
        AAAANgAArhQAAFHsAABD1wAAsKQAACZmAAAPXAAAUA0AAFQ5AAIzMwACMzMAAjMzAAAAAAAAAABt
        bW9kAAAAAAAABhAAAKBP/WJtYgAAAAAAAAAAAAAAAAAAAAAAAAAAdmNncAAAAAAAAwAAAAJmZgAD
        AAAAAmZmAAMAAAACZmYAAAACMzM0AAAAAAIzMzQAAAAAAjMzNAD/2wCEAAEBAQEBAQIBAQIDAgIC
        AwQDAwMDBAYEBAQEBAYHBgYGBgYGBwcHBwcHBwcICAgICAgJCQkJCQsLCwsLCwsLCwsBAgICAwMD
        BQMDBQsIBggLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsLCwsL
        C//dAAQABP/AABEIAAoAMwMBIgACEQEDEQH/xAGiAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUGBwgJ
        CgsQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJ
        ChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeI
        iYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq
        8fLz9PX29/j5+gEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoLEQACAQIEBAMEBwUEBAABAncA
        AQIDEQQFITEGEkFRB2FxEyIygQgUQpGhscEJIzNS8BVictEKFiQ04SXxFxgZGiYnKCkqNTY3ODk6
        Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqCg4SFhoeIiYqSk5SVlpeYmZqio6Slpqeo
        qaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2dri4+Tl5ufo6ery8/T19vf4+fr/2gAMAwEAAhED
        EQA/AP7+KKKKAPHJ7D41JrLy29/YvYtI+2MxbWWMk7ef7wGB6cdKX7B8a49ka39g4OAzGLBGFwSA
        OOTz7fSvYqKw+rr+Z/edv15/8+4/+Aop6cl5Hp8Caiwe4WNRKyjALgckD61coordHG3fUKKKKBH/
        2Q==
        BASE64;

        $uid = self::$sut->append(
            <<<RFC822
            MIME-Version: 1.0
            Date: Sat, 27 Apr 2024 20:49:48 +0200
            Message-ID: <CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>
            Subject: Lorem ipsum
            From: Sender <sender@localhost>
            To: User <user@localhost>
            Content-Type: multipart/mixed; boundary="000000000000edbcbd061017ee91"
            
            --000000000000edbcbd061017ee91
            Content-Type: multipart/alternative; boundary="000000000000edbcbb061017ee8f"
            
            --000000000000edbcbb061017ee8f
            Content-Type: text/plain; charset="UTF-8"
            
            Dolor sit amet
            
            --000000000000edbcbb061017ee8f
            Content-Type: text/html; charset="UTF-8"
            
            <div dir="ltr">Dolor sit amet</div>
            
            --000000000000edbcbb061017ee8f--
            --000000000000edbcbd061017ee91
            Content-Type: image/jpeg; name="attachment-example.jpg"
            Content-Disposition: attachment; filename="attachment-example.jpg"
            Content-Transfer-Encoding: base64
            X-Attachment-Id: f_lrz4q1xh0
            Content-ID: <f_lrz4q1xh0>
            
            $attachment
            --000000000000edbcbd061017ee91--

            RFC822);

        $message = self::$sut->fetch($uid);

        $this->assertEquals('Dolor sit amet', $message->textBody());
        $this->assertEquals('<div dir="ltr">Dolor sit amet</div>', $message->htmlBody());
        $this->assertEquals('attachment', $message->body()->parts[1]->disposition()->type);
        $this->assertEquals($attachment, $message->body()->parts[1]->body());
    }

    #[Test]
    #[Depends('mailbox')]
    public function fetchMissing()
    {
        $this->expectException(MessageNotFound::class);

        self::$sut->fetch(999999999);
    }

    #[Test]
    #[Depends('mailbox')]
    #[DoesNotPerformAssertions]
    public function deleteMessage()
    {
        $id = self::$sut->append(
            <<<RFC822
            MIME-Version: 1.0
            Date: Sat, 27 Apr 2024 20:49:48 +0200
            Message-ID: <CAMjJg5jatty9mNkfS871w6=oDqXGETmpT9Y6_b7vU8_vz_yYMw@example.com>
            Subject: Lorem ipsum
            From: Sender <sender@localhost>
            To: User <user@localhost>
            Content-Type: text/plain; charset="UTF-8"
            
            Dolor sit amet
            RFC822
        );

        self::$sut->deleteMessage($id);
    }

    #[Test]
    #[Depends('logIn')]
    public function createMailbox()
    {
        $name = uniqid();

        self::$sut->createMailbox($name);

        $this->assertContains(
            $name,
            array_map(fn (Mailbox $mailbox) => $mailbox->name, self::$sut->list())
        );
    }

    #[Test]
    public function createClientWithLogger()
    {
        $logger = new InMemoryLogger();

        $sut = Client::create(self::$configuration, $logger);

        $sut->logIn('user', 'pass');

        $this->assertEquals(
            [
                'debug' => [
                    ['* OK IMAP4rev1 Server GreenMail v2.1.0-alpha-4 ready\r\n'],
                    ['A001 LOGIN \"user\" \"pass\"\r\n'],
                    ['A001 OK LOGIN completed.\r\n']
                ]
            ],
            $logger->logs,
        );
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function authenticateUsingOAuth2()
    {
        $this->markTestSkipped(
            'This tests requires a valid oauth2 access token. ' .
            'To test it, replace the access token placeholder with a valid access token and comment this line'
        );

        $client = Client::create(new Configuration(
            'ssl',
            'imap.gmail.com',
            993,
        ));

        $user = 'user@example.com';
        $accessToken = 'replace-with-access-token';

        $client->authenticate(new XOAuth2($user, $accessToken));
    }
}
