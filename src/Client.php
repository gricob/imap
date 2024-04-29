<?php

namespace Gricob\IMAP;

use Gricob\IMAP\Mime\LazyMessage;
use Gricob\IMAP\Mime\Message;
use Gricob\IMAP\Mime\Part\Disposition;
use Gricob\IMAP\Mime\Part\LazyBody;
use Gricob\IMAP\Mime\Part\MultiPart;
use Gricob\IMAP\Mime\Part\Part;
use Gricob\IMAP\Mime\Part\SinglePart;
use Gricob\IMAP\Protocol\Command\AppendCommand;
use Gricob\IMAP\Protocol\Command\Argument\Search\Criteria;
use Gricob\IMAP\Protocol\Command\Argument\SequenceSet;
use Gricob\IMAP\Protocol\Command\Command;
use Gricob\IMAP\Protocol\Command\FetchCommand;
use Gricob\IMAP\Protocol\Command\ListCommand;
use Gricob\IMAP\Protocol\Command\SelectCommand;
use Gricob\IMAP\Protocol\Command\LogInCommand;
use Gricob\IMAP\Protocol\Imap;
use Gricob\IMAP\Protocol\Response\Line\Data\FetchData;
use Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructure as BodyStructure;
use Gricob\IMAP\Protocol\Response\Line\Data\ListData;
use Gricob\IMAP\Protocol\Response\Line\Data\SearchData;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\AppendUidCode;
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
        $this->send(new LogInCommand($username, $password));
    }

    /**
     * @return array<Mailbox>
     */
    public function list(string $referenceName = '', string $pattern = '*'): array
    {
        $response = $this->send(new ListCommand($referenceName, $pattern));

        return array_map(
            fn (ListData $data) => new Mailbox($data->nameAttributes, $data->hierarchyDelimiter, $data->name),
            $response->getData(ListData::class),
        );
    }

    public function select(string $mailbox): self
    {
        $this->send(new SelectCommand($mailbox));

        return $this;
    }

    public function search(): Search
    {
        return new Search($this);
    }

    /**
     * @throws MessageNotFound
     */
    public function fetch(int $id): Message
    {
        $response = $this->imap->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id, $id),
                ['INTERNALDATE', 'BODY[HEADER]', 'BODYSTRUCTURE']
            )
        );

        /** @var FetchData $data */
        $data = $response->getData(FetchData::class)[0] ?? throw new MessageNotFound();

        $rawHeaders = $data->getBodySection('HEADER')?->text ?? '';
        $headers = iconv_mime_decode_headers($rawHeaders);

        return new Message(
            $headers,
            $this->createMessagePart($id, '0', $data->bodyStructure->part),
            $data->internalDate->date,
        );
    }

    public function fetchHeaders(int $id): array
    {
        $response = $this->imap->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id, $id),
                ['BODY[HEADER]']
            )
        );

        /** @var FetchData $data */
        $data = $response->getData(FetchData::class)[0] ?? throw new MessageNotFound();

        $rawHeaders = $data->getBodySection('HEADER')?->text ?? '';
        return iconv_mime_decode_headers($rawHeaders);
    }

    public function fetchBody(int $id): Part
    {
        $response = $this->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id, $id),
                ["BODYSTRUCTURE"]
            )
        );

        $data = $response->getData(FetchData::class)[0];

        return $this->createMessagePart($id, '0', $data->bodyStructure->part);
    }

    public function fetchInternalDate(int $id): \DateTimeImmutable
    {
        $response = $this->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id, $id),
                ["INTERNALDATE"]
            )
        );

        $data = $response->getData(FetchData::class)[0];

        return $data->internalDate->date;
    }

    public function fetchSectionBody(int $id, string $section): string
    {
        $response = $this->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id, $id),
                ["BODY[$section]"]
            )
        );

        $data = $response->getData(FetchData::class)[0];

        return $data->getBodySection($section)->text;
    }

    public function append(
        string $message,
        string $mailbox = 'INBOX',
        ?array $flags = null,
        ?\DateTimeInterface $internalDate = null
    ): int
    {
        $response = $this->send(new AppendCommand($mailbox, $message, $flags, $internalDate));

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

    /**
     * @param Criteria ...$criteria
     * @return array<Message>
     */
    public function doSearch(Criteria ...$criteria): array
    {
        $response = $this->send(
            new Protocol\Command\SearchCommand(
                $this->configuration->useUid,
                ...$criteria
            )
        );

        $result = [];
        foreach ($response->data as $data) {
            if ($data instanceof SearchData) {
                foreach ($data->numbers as $id) {
                    $result[] = new LazyMessage($this, $id);
                }
            }
        }

        return $result;
    }

    private function createMessagePart(int $id, string $section, BodyStructure\Part $part): Mime\Part\Part
    {
        if ($part instanceof BodyStructure\SinglePart) {
            return new SinglePart(
                $part->type,
                $part->subtype,
                $part->attributes,
                new LazyBody($this, $id, $section === '0' ? '1' : $section),
                $part->attributes['charset'] ?? 'utf-8',
                $part->encoding,
                null !== $part->disposition
                    ? new Disposition(
                        $part->disposition,
                        $part->dispositionAttributes['filename'] ?? null
                    ) : null,
            );
        }

        if (!$part instanceof BodyStructure\MultiPart) {
            throw new \Exception('Unable to create message part from body structure part of class '.$part::class);
        }

        $childParts = [];
        foreach ($part->parts as $index => $childPart) {
            $childIndex = ($index + 1);
            $childSection = $section === '0' ? $childIndex : $section.'.'.$childIndex;
            $childParts[] = $this->createMessagePart($id, $childSection, $childPart);
        }

        return new MultiPart($part->subtype, $part->attributes, $childParts);
    }
}