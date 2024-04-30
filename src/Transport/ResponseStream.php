<?php

namespace Gricob\IMAP\Transport;

final class ResponseStream
{
    /**
     * @param resource $stream
     */
    public function __construct(private $stream)
    {
    }

    public function read(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }

        $remainingBytes = $bytes;
        $data = '';
        do {
            $data .= fread($this->stream, $remainingBytes);
            $remainingBytes = $bytes - strlen($data);
        } while ($remainingBytes > 0);

        return $data;
    }

    public function readLine(): string
    {
        $line = '';

        while ("\n" !== ($char = fread($this->stream, 1))) {
            $line .= $char;
        }

        return $line."\n";
    }
}