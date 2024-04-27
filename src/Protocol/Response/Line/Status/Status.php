<?php

namespace Gricob\IMAP\Protocol\Response\Line\Status;

use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\AppendUidCode;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\Code;

abstract readonly class Status implements Line
{
    private const PATTERN = '/^(?<tag>\*|[a-zA-Z0-9]*) {STATUS}( \[(?<code>.*)\])?( (?<message>(.*)))?/';

    /**
     * @var array<Code>
     */
    private const CODES = [
        AppendUidCode::class,
    ];

    final public function __construct(
        public string $tag,
        public ?Code $code,
        public string $message
    ) {
    }

    abstract public static function status(): string;

    public static function tryParse(string $raw): ?static
    {
        $pattern = str_replace('{STATUS}', preg_quote(static::status()), self::PATTERN);

        if (!preg_match($pattern, $raw, $matches)) {
            return null;
        }

        if (isset($matches['code'])) {
            foreach (self::CODES as $CODE) {
                if (null !== ($code = $CODE::tryParse($matches['code']))) {
                    break;
                }
            }
        }

        return new static(
            $matches['tag'],
            $code ?? null,
            $matches['message'] ?? ''
        );
    }

    public function isCompletionStatus(): bool
    {
        return $this->tag != '*';
    }
}