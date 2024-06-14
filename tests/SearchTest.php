<?php

declare(strict_types=1);

namespace Tests;

use Gricob\IMAP\Client;
use Gricob\IMAP\PreFetchOptions;
use Gricob\IMAP\Protocol\Command\Argument\Search\All;
use Gricob\IMAP\Protocol\Command\Argument\Search\Before;
use Gricob\IMAP\Protocol\Command\Argument\Search\Criteria;
use Gricob\IMAP\Protocol\Command\Argument\Search\Header;
use Gricob\IMAP\Protocol\Command\Argument\Search\Not;
use Gricob\IMAP\Protocol\Command\Argument\Search\Since;
use Gricob\IMAP\Search;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
{
    private Client|MockObject $clientMock;
    private Search $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = $this->createMock(Client::class);
        $this->sut = new Search($this->clientMock);
    }

    #[Test]
    public function getGivenNotWithoutSearchKeySpecified(): void
    {
        $this->expectException(\BadMethodCallException::class);

        $this->sut->not()->get();
    }

    #[Test]
    public function getGivenHeader(): void
    {
        $this->expectSearch([new Header('In-Reply-To', '')]);

        $this->sut->header('In-Reply-To')->get();
    }

    #[Test]
    public function getGivenBefore(): void
    {
        $date = new \DateTimeImmutable();
        $this->expectSearch([new Before($date)]);

        $this->sut->before($date)->get();
    }

    #[Test]
    public function getGivenSince(): void
    {
        $date = new \DateTimeImmutable();
        $this->expectSearch([new Not(new Since($date))]);

        $this->sut->not()->since($date)->get();
    }

    #[Test]
    public function getGivenNotHeader(): void
    {
        $this->expectSearch([new Not(new Header('In-Reply-To', ''))]);

        $this->sut->not()->header('In-Reply-To')->get();
    }

    #[Test]
    public function getGivenNotBefore(): void
    {
        $date = new \DateTimeImmutable();
        $this->expectSearch([new Not(new Before($date))]);

        $this->sut->not()->before($date)->get();
    }

    #[Test]
    public function getGivenNotSince(): void
    {
        $date = new \DateTimeImmutable();
        $this->expectSearch([new Not(new Since($date))]);

        $this->sut->not()->since($date)->get();
    }

    #[Test]
    public function getGivenPreFetchOptions(): void
    {
        $preFetchOptions = new PreFetchOptions(true, true);
        $this->expectSearch([new All()], $preFetchOptions);

        $this->sut->get($preFetchOptions);
    }

    private function expectSearch(array $criteria): void
    {
        $this->clientMock->expects(self::once())
            ->method('doSearch')
            ->with($criteria)
            ->willReturn([]);
    }
}