<?php

namespace Gricob\IMAP\Protocol\Response\Parser;

final class ParseError extends \Exception
{
    /**
     * @param TokenType[] $expected
     */
    public static function unexpectedToken(?TokenType $given, array $expected): self
    {
        return new self(
            sprintf(
                'Expected token of type %s. Given %s',
                implode(
                    ' or ',
                    array_map(fn (TokenType $type) => $type->name, $expected)
                ),
                $given?->name ?? 'null',
            )
        );
    }
}