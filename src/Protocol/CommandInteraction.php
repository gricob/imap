<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

use Gricob\IMAP\Protocol\Command\Command;
use Gricob\IMAP\Protocol\Command\Continuable;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Transport\Connection;
use RuntimeException;

final readonly class CommandInteraction implements ContinuationHandler
{
    public function __construct(
        private Connection $connection,
        private ResponseHandler $responseHandler,
        private string $tag,
        private Command $command,
    ) {
    }

    public function interact(): Response
    {
        $request = sprintf(
            "%s %s\r\n",
            $this->tag,
            $this->command,
        );

        $this->connection->send($request);
        $streamResponse = $this->connection->receive();

        return $this->responseHandler->handle($this->tag, $streamResponse, $this);
    }

    public function continue(): void
    {
        if (!$this->command instanceof Continuable) {
            throw new RuntimeException(
                sprintf('Command %s does not support continuable interaction', $this->command->command())
            );
        }

        $this->connection->send($this->command->continue()."\r\n");
    }
}