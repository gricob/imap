<?php

namespace Gricob\IMAP\Transport;

class Connection
{
    private string $transport;
    private string $host;
    private int $port;
    private float $timeout;
    private bool $verifyPeer;
    private bool $allowSelfSigned;
    private bool $verifyPeerName;

    private ?int $errorCode;
    private ?string $errorMessage;

    /**
     * @var resource|null
     */
    private $stream = null;

    public function __construct(
        string $transport,
        string $host,
        int $port,
        float $timeout,
        bool $verifyPeer = true,
        bool $verifyPeerName = true,
        bool $allowSelfSigned = false,
    ) {
        $this->port = $port;
        $this->host = $host;
        $this->transport = $transport;
        $this->timeout = $timeout;
        $this->verifyPeer = $verifyPeer;
        $this->verifyPeerName = $verifyPeerName;
        $this->allowSelfSigned = $allowSelfSigned;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function isOpen(): bool
    {
        return null !== $this->stream;
    }

    public function open(): void
    {
        if ($this->isOpen()) {
            return;
        }

        $this->stream = stream_socket_client(
            sprintf('%s://%s:%s', $this->transport, $this->host, $this->port),
            $this->errorCode,
            $this->errorMessage,
            $this->timeout,
            context: stream_context_create([
                'ssl' => [
                    'verify_peer' => $this->verifyPeer,
                    'verify_peer_name' => $this->verifyPeerName,
                    'allow_self_signed' => $this->allowSelfSigned,
                ]
            ])
        );

        if (false === $this->stream) {
            throw new ConnectionFailed($this->errorMessage ?? 'Connection failed');
        }
    }

    public function close(): void
    {
        if (!$this->stream) {
            return;
        }

        fclose($this->stream);

        $this->stream = null;
    }

    public function send(string $data): void
    {
        fwrite($this->stream, $data);
    }

    public function receive(): ResponseStream
    {
        return new ResponseStream($this->stream);
    }
}