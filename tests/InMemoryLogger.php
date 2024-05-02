<?php

namespace Tests;

use Psr\Log\LoggerInterface;

class InMemoryLogger implements LoggerInterface
{
    public array $logs = [];

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->logs['emergency'][] = func_get_args();
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->logs['alert'] = func_get_args();
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->logs['critical'][] = func_get_args();
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->logs['error'][] = func_get_args();
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->logs['warning'][] = func_get_args();
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->logs['notice'][] = func_get_args();
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->logs['info'][] = func_get_args();
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->logs['debug'][] = func_get_args();
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[$level][] = [$message, $context];
    }
}