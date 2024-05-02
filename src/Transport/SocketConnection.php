<?php

namespace Gricob\IMAP\Transport;

class SocketConnection implements Connection
{
    private string $transport;
    private string $host;
    private int $port;
    private float $timeout;
    private bool $verifyPeer;
    private bool $allowSelfSigned;
    private bool $verifyPeerName;

    /**
     * @var resource|false
     */
    private $stream = false;

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
        return false !== $this->stream;
    }

    public function open(): void
    {
        if ($this->isOpen()) {
            return;
        }

        $this->stream = stream_socket_client(
            sprintf('%s://%s:%s', $this->transport, $this->host, $this->port),
            $errorCode,
            $errorMessage,
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
            throw new ConnectionFailed(
                sprintf('SocketConnection failed [%s]: %s', $errorCode, $errorMessage)
            );
        }
    }

    public function close(): void
    {
        if (!$this->stream) {
            return;
        }

        fclose($this->stream);

        $this->stream = false;
    }

    public function send(string $data): void
    {
        if (!$this->stream) {
            throw new \Exception('Unable to send data. SocketConnection is not open');
        }

        fwrite($this->stream, $data);
    }

    public function receive(): ResponseStream
    {
        if (!$this->stream) {
            throw new \Exception('Unable to receive data. SocketConnection is not open');
        }

        return new SocketResponseStream($this->stream);
    }
}