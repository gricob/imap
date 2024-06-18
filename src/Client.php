<?php

declare(strict_types=1);

namespace Gricob\IMAP;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
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
use Gricob\IMAP\Protocol\Command\Argument\Store\Flags;
use Gricob\IMAP\Protocol\Command\Authenticate\SASLMechanism;
use Gricob\IMAP\Protocol\Command\AuthenticateCommand;
use Gricob\IMAP\Protocol\Command\Command;
use Gricob\IMAP\Protocol\Command\CreateCommand;
use Gricob\IMAP\Protocol\Command\ExpungeCommand;
use Gricob\IMAP\Protocol\Command\FetchCommand;
use Gricob\IMAP\Protocol\Command\ListCommand;
use Gricob\IMAP\Protocol\Command\LogInCommand;
use Gricob\IMAP\Protocol\Command\SelectCommand;
use Gricob\IMAP\Protocol\Command\StoreCommand;
use Gricob\IMAP\Protocol\Imap;
use Gricob\IMAP\Protocol\Response\Line\Data\FetchData;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;
use Gricob\IMAP\Protocol\Response\Line\Data\ListData;
use Gricob\IMAP\Protocol\Response\Line\Data\SearchData;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\AppendUidCode;
use Gricob\IMAP\Protocol\Response\Response;
use Gricob\IMAP\Transport\Socket\SocketConnection;
use Gricob\IMAP\Transport\Traceable\TraceableConnection;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Client
{
    public Configuration $configuration;
    private Imap $imap;

    private ?string $selectedMailbox = null;

    private function __construct(
        Configuration $configuration,
        ?LoggerInterface $logger,
    ) {
        $connection = new SocketConnection(
            $configuration->transport,
            $configuration->host,
            $configuration->port,
            $configuration->timeout,
            $configuration->verifyPeer,
            $configuration->verifyPeerName,
            $configuration->allowSelfSigned,
        );

        if (null !== $logger) {
            $connection = new TraceableConnection($connection, $logger);
        }

        $this->configuration = $configuration;
        $this->imap = new Imap($connection);
    }

    public static function create(Configuration $configuration, ?LoggerInterface $logger = null): self
    {
        return new self($configuration, $logger);
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

    public function authenticate(SASLMechanism $mechanism): void
    {
        $this->send(new AuthenticateCommand($mechanism));
    }

    /**
     * @return array<Mailbox>
     */
    public function mailboxes(string $referenceName = '', string $pattern = '*'): array
    {
        $response = $this->send(new ListCommand($referenceName, $pattern));

        return array_map(
            fn (ListData $data) => new Mailbox($data->nameAttributes, $data->hierarchyDelimiter, $data->name),
            $response->getData(ListData::class),
        );
    }

    public function select(Mailbox|string $mailbox): void
    {
        $mailboxName = $mailbox instanceof Mailbox ? $mailbox->name : $mailbox;

        if ($this->selectedMailbox === $mailboxName) {
            return;
        }

        $this->send(new SelectCommand($mailboxName));
        $this->selectedMailbox = $mailboxName;
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
                new SequenceSet($id),
                ['INTERNALDATE', 'BODY[HEADER]', 'BODYSTRUCTURE']
            )
        );

        $data = $response->getData(FetchData::class)[0] ?? throw new MessageNotFound();

        if (null === $internalDate = $data->internalDate) {
            throw new Exception('Unable to fetch internal date from message '.$id);
        }

        if (null === $part = $data->bodyStructure?->part) {
            throw new Exception('Unable to fetch body structure from message '.$id);
        }

        return new Message(
            $id,
            $this->createHeaders($data) ?? [],
            $this->createMessagePart($id, '0', $part),
            $internalDate,
        );
    }

    /**
     * @return array<string, string>
     * @throws MessageNotFound
     */
    public function fetchHeaders(int $id): array
    {
        $response = $this->imap->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id),
                ['BODY[HEADER]']
            )
        );

        /** @var FetchData $data */
        $data = $response->getData(FetchData::class)[0] ?? throw new MessageNotFound();

        return $this->createHeaders($data) ?? [];
    }

    public function fetchBody(int $id): Part
    {
        $response = $this->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id),
                ['BODYSTRUCTURE']
            )
        );

        $data = $response->getData(FetchData::class)[0];

        if (null === $part = $data->bodyStructure?->part) {
            throw new Exception('Unable to fetch body from message '.$id);
        }

        return $this->createMessagePart($id, '0', $part);
    }

    public function fetchInternalDate(int $id): DateTimeImmutable
    {
        $response = $this->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id),
                ['INTERNALDATE']
            )
        );

        $data = $response->getData(FetchData::class)[0];

        if (null === $internalDate = $data->internalDate) {
            throw new Exception('Unable to fetch internal date from message '.$id);
        }

        return $internalDate;
    }

    public function fetchSectionBody(int $id, string $section): string
    {
        $response = $this->send(
            new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet($id),
                ["BODY[$section]"]
            )
        );

        $data = $response->getData(FetchData::class)[0];

        return $data->getBodySection($section)?->text ?? '';
    }

    public function deleteMessage(Message|int $message): void
    {
        $id = $message instanceof Message ? $message->id() : $message;

        $this->send(
            new StoreCommand(
                $this->configuration->useUid,
                new SequenceSet($id),
                new Flags(['\Deleted'], '+')
            )
        );

        $this->send(new ExpungeCommand());
    }

    public function createMailbox(string $name): void
    {
        $this->send(new CreateCommand($name));
    }

    /**
     * @param list<string>|null $flags
     */
    public function append(
        string $message,
        string $mailbox = 'INBOX',
        ?array $flags = null,
        ?DateTimeInterface $internalDate = null
    ): int
    {
        $response = $this->send(new AppendCommand($mailbox, $message, $flags, $internalDate));

        $code = $response->status->code;
        if ($code instanceof AppendUidCode) {
            return $code->uid;
        }

        throw new RuntimeException('Unable to retrieve uid from append response');
    }

    public function send(Command $command): Response
    {
        $this->imap->connect();

        return $this->imap->send($command);
    }

    /**
     * @param array<Criteria> $criteria
     * @return array<Message>
     */
    public function doSearch(array $criteria, ?PreFetchOptions $preFetchOptions = null): array
    {
        $response = $this->send(
            new Protocol\Command\SearchCommand(
                $this->configuration->useUid,
                ...$criteria
            )
        );

        $ids = [];
        foreach ($response->data as $data) {
            if ($data instanceof SearchData) {
                array_push($ids, ...$data->numbers);
            }
        }

        if (empty($ids)) {
            return [];
        }

        if (null !== $preFetchOptions) {
            $items = [];

            if ($preFetchOptions->headers) {
                $items[] = 'BODY[HEADER]';
            }

            if ($preFetchOptions->internalDate) {
                $items[] = 'INTERNALDATE';
            }

            $preFetchResult = $this->send(new FetchCommand(
                $this->configuration->useUid,
                new SequenceSet(...$ids),
                $items,
            ));

            $messages = [];
            foreach ($preFetchResult->data as $data) {
                if ($data instanceof FetchData) {
                    $id = $data->id;
                    if ($this->configuration->useUid) {
                        $id = $data->uid ?? throw new RuntimeException('Unable to get uid from message '.$id);
                    }

                    $messages[] = new LazyMessage(
                        $this,
                        $id,
                        $this->createHeaders($data),
                        $data->internalDate,
                    );
                }
            }

            return $messages;
        }

        return array_map(fn (int $id) => new LazyMessage($this, $id), $ids);
    }

    /**
     * @return array<string, string>|null
     */
    private function createHeaders(FetchData $data): ?array
    {
        if (null === $headerSection = $data->getBodySection('HEADER')) {
            return null;
        }

        return iconv_mime_decode_headers($headerSection->text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR) ?: [];
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
                        $part->disposition->type,
                        $part->disposition->attributes['filename'] ?? null
                    ) : null,
            );
        }

        if (!$part instanceof BodyStructure\MultiPart) {
            throw new Exception('Unable to create message part from body structure part of class '.$part::class);
        }

        $childParts = [];
        foreach ($part->parts as $index => $childPart) {
            $childIndex = (string) ($index + 1);
            $childSection = $section === '0' ? $childIndex : $section.'.'.$childIndex;
            $childParts[] = $this->createMessagePart($id, $childSection, $childPart);
        }

        return new MultiPart($part->subtype, $part->attributes, $childParts);
    }
}
