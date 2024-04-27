<?php

namespace Gricob\IMAP;

use Gricob\IMAP\Protocol\Command\Append;
use Gricob\IMAP\Protocol\Command\Command;
use Gricob\IMAP\Protocol\Command\ListCommand;
use Gricob\IMAP\Protocol\Command\Select;
use Gricob\IMAP\Protocol\Command\LogIn;
use Gricob\IMAP\Protocol\Imap;
use Gricob\IMAP\Protocol\Response\Line\Data\ListData;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\AppendUidCode;
use Gricob\IMAP\Protocol\Response\Line\Status\OkStatus;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Transport\Connection;

readonly class Client
{
    public Configuration $configuration;
    private Imap $imap;

    private function __construct(Configuration $configuration)
    {
        $connection = new Connection(
            $configuration->transport,
            $configuration->host,
            $configuration->port,
            $configuration->timeout,
            $configuration->verifyPeer,
            $configuration->verifyPeerName,
            $configuration->allowSelfSigned,
        );

        $this->configuration = $configuration;
        $this->imap = new Imap($connection);
    }

    public static function create(Configuration $configuration): self
    {
        return new self($configuration);
    }

    public function connect(): void
    {
        $this->imap->connect();
    }

    public function disconnect(): void
    {
        $this->imap->disconnect();
    }

    public function logIn(string $username, string $password): void
    {
        $this->send(new LogIn($username, $password));
    }

    /**
     * @return array<Mailbox>
     */
    public function list(string $referenceName = '', string $pattern = '*'): array
    {
        $response = $this->send(new ListCommand($referenceName, $pattern));

        $mailboxes = [];
        foreach ($response->data as $data) {
            if ($data instanceof ListData) {
                $mailboxes[] = new Mailbox($data->nameAttributes, $data->hierarchyDelimiter, $data->name);
            }
        }

        return $mailboxes;
    }

    public function select(string $mailbox): self
    {
        $this->send(new Select($mailbox));

        return $this;
    }

    public function search(): Search
    {
        return new Search($this);
    }

    public function append(
        string $message,
        string $mailbox = 'INBOX',
        ?array $flags = null,
        ?\DateTimeInterface $internalDate = null
    ): int
    {
        $response = $this->send(new Append($mailbox, $message, $flags, $internalDate));

        $code = $response->status->code;
        if ($code instanceof AppendUidCode) {
            return $code->uid;
        }

        throw new \RuntimeException('Unable to retrieve uid from append response');
    }

    public function send(Command $command): Response
    {
        $this->imap->connect();

        return $this->imap->send($command);
    }
}