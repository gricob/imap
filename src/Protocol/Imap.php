<?php

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Command\Command;
use Gricob\IMAP\Protocol\Response\Line\Status\BadStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\ByeStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\NoStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\OkStatus;
use Gricob\IMAP\Protocol\Response\Line\Status\PreAuthStatus;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Transport\Connection;

class Imap
{
    private Connection $connection;
    private TagGenerator $tagGenerator;
    private ResponseHandler $responseHandler;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->tagGenerator = new TagGenerator();
        $this->responseHandler = new ResponseHandler();
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

        match (true) {
            $greeting->status instanceof OkStatus => null, // Do nothing
            $greeting->status instanceof PreAuthStatus => throw new \RuntimeException('pre-auth is not supported'),
            $greeting->status instanceof BadStatus,
            $greeting->status instanceof NoStatus,
            $greeting->status instanceof ByeStatus => throw new ConnectionRejected($greeting->status->message),
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

        if (!$response->status instanceof OkStatus) {
            throw CommandFailed::withStatus($response->status);
        }

        return $response;
    }
}