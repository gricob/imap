<?php

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Response\Line\CommandContinuation;
use Gricob\IMAP\Protocol\Response\Line\Data\CapabilityData;
use Gricob\IMAP\Protocol\Response\Line\Data\ExistsData;
use Gricob\IMAP\Protocol\Response\Line\Data\FlagsData;
use Gricob\IMAP\Protocol\Response\Line\Data\RecentData;
use Gricob\IMAP\Protocol\Response\Line\Data\SearchData;
use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\BadStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\ByeStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\NoStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\OkStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\PreAuthStatus;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Protocol\Response\ResponseBuilder;
use Gricob\IMAP\Transport\ResponseStream;

class ResponseHandler
{
    /**
     * @var class-string<Line>[]
     */
    private const RESPONSE_LINES = [
        // Status lines
        BadStatus::class,
        ByeStatus::class,
        NoStatus::class,
        OkStatus::class,
        PreAuthStatus::class,
        // Data lines
        CapabilityData::class,
        ExistsData::class,
        FlagsData::class,
        RecentData::class,
        SearchData::class,
        // Continuation
        CommandContinuation::class,
    ];

    public function handle(string $statusTag, ResponseStream $stream, ContinuationHandler $continuationHandler): Response
    {
        $responseBuilder = new ResponseBuilder($statusTag);

        do {
            $raw = $stream->readLine();
            if (preg_match('/\{(?<bytes>\d+)}\r\n$/', $raw, $matches)) {
                $raw .= $stream->read((int) $matches['bytes']);
                $raw .= $stream->readLine();
            }
            $line = $this->parseRawLine($raw);

            if ($line instanceof CommandContinuation) {
                $continuationHandler->continue();
                continue;
            }

            $responseBuilder->addLine($line);
        } while (!$responseBuilder->hasStatus());

        return $responseBuilder->build();
    }

    private function parseRawLine(string $raw): Line
    {
        foreach (self::RESPONSE_LINES as $LINE) {
            if (null !== ($line = $LINE::tryParse($raw))) {
                return $line;
            }
        }

        throw new \RuntimeException('Unable to find parser for response line: '.$raw);
    }
}