<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Command\Command;
use Gricob\IMAP\Protocol\Response\Line\Status\StatusType;
use Gricob\IMAP\Protocol\Response\Parser\Parser;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Transport\Connection;
use RuntimeException;

class Imap
{
    private Connection $connection;
    private TagGenerator $tagGenerator;
    private ResponseHandler $responseHandler;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->tagGenerator = new TagGenerator();
        $this->responseHandler = new ResponseHandler(new Parser());
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        if ($this->connection->isOpen()) {
            return;
        }

        $this->connection->open();

        $responseStream = $this->connection->receive();

        $greeting = $this->responseHandler->handle('*', $responseStream, new UnexpectedContinuationHandler());

        match ($greeting->status->type) {
            StatusType::OK => null, // Do nothing
            StatusType::PREAUTH => throw new RuntimeException('pre-auth is not supported'),
            StatusType::BAD,
            StatusType::NO,
            StatusType::BYE => throw new ConnectionRejected($greeting->status->message),
        };
    }

    public function disconnect(): void
    {
        $this->connection->close();
    }

    public function send(Command $command): Response
    {
        $interaction = new CommandInteraction(
            $this->connection,
            $this->responseHandler,
            $this->tagGenerator->next(),
            $command,
        );

        $response = $interaction->interact();

        if ($response->status->type != StatusType::OK) {
            throw CommandFailed::withStatus($response->status);
        }

        return $response;
    }
}