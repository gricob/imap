<?php

declare(strict_types=1);

namespace Tests\Mime\Part;

use Gricob\IMAP\Mime\Part\Body;
use Gricob\IMAP\Mime\Part\SinglePart;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SinglePartTest extends TestCase
{
    #[Test]
    public function shouldDecodeBodyBase64()
    {
        $sut = new SinglePart(
            "text",
            "html",
            ["CHARSET" => "utf-8"],
            new Body("PGRpdiBkaXI9Imx0ciI+RG9sb3Igc2l0IGFtZXQ8L2Rpdj4="),
            "utf-8",
            "base64",
            null
        );

        $this->assertEquals(
            '<div dir="ltr">Dolor sit amet</div>',
            $sut->decodedBody()
        );
    }
}
