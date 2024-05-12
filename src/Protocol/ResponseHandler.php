<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Response\Line\CommandContinuation;
use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Parser\Parser;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Protocol\Response\ResponseBuilder;
use Gricob\IMAP\Transport\ResponseStream;
use RuntimeException;

readonly class ResponseHandler
{
    public function __construct(private Parser $parser)
    {
    }

    public function handle(string $statusTag, ResponseStream $stream, ContinuationHandler $continuationHandler): Response
    {
        $responseBuilder = new ResponseBuilder($statusTag);

        do {
            $raw = $stream->readLine();
            while (preg_match('/\{(?<bytes>\d+)}\r\n$/', $raw, $matches)) {
                $raw .= $stream->read((int) $matches['bytes']);
                $raw .= $stream->readLine();
            }
            $line = $this->parser->parse($raw);

            if ($line instanceof CommandContinuation) {
                $continuationHandler->continue();
                continue;
            }

            $responseBuilder->addLine($line);
        } while (!$responseBuilder->hasStatus());

        return $responseBuilder->build();
    }
}