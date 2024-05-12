<?php

namespace Gricob\IMAP\Protocol\Response\Parser;

use Doctrine\Common\Lexer\AbstractLexer;

/**
 * @extends AbstractLexer<TokenType, string>
 */
class Lexer extends AbstractLexer
{
    protected function getCatchablePatterns(): array
    {
        return [
            '[a-zA-Z0-9\.]+',
            '\r\n',
        ];
    }

    protected function getNonCatchablePatterns(): array
    {
        return [];
    }

    protected function getType(string &$value)
    {
        $normalizedValue = strtoupper($value);

        return match($normalizedValue) {
            ' ' => TokenType::SP,
            '.' => TokenType::DOT,
            '*' => TokenType::ASTERISK,
            '%' => TokenType::PERCENT_SIGN,
            '+' => TokenType::PLUS_SIGN,
            '=' => TokenType::EQUALS_SIGN,
            '"' => TokenType::DOUBLE_QUOTE,
            '[' => TokenType::OPEN_BRACKETS,
            ']' => TokenType::CLOSE_BRACKETS,
            '{' => TokenType::OPEN_BRACES,
            '}' => TokenType::CLOSE_BRACES,
            '(' => TokenType::OPEN_PARENTHESIS,
            ')' => TokenType::CLOSE_PARENTHESIS,
            '\\' => TokenType::BACKSLASH,
            "\r\n" => TokenType::CRLF,
            'NIL' => TokenType::NIL,
            'OK', 'NO', 'BAD', 'BYE', 'PREAUTH' => TokenType::STATUS,
            'APPENDUID' => TokenType::APPENDUID,
            'CAPABILITY' => TokenType::CAPABILITY,
            'LIST' => TokenType::LIST,
            'FLAGS' => TokenType::FLAGS,
            'RECENT' => TokenType::RECENT,
            'FETCH' => TokenType::FETCH,
            'INTERNALDATE' => TokenType::INTERNALDATE,
            'SEARCH' => TokenType::SEARCH,
            'EXISTS' => TokenType::EXISTS,
            'EXPUNGE' => TokenType::EXPUNGE,
            'BODY' => TokenType::BODY,
            'BODYSTRUCTURE' => TokenType::BODYSTRUCTURE,
            'ENVELOPE' => TokenType::ENVELOPE,
            'RFC822' => TokenType::RFC822,
            'RFC822.SIZE' => TokenType::RFC822_SIZE,
            'RFC822.TEXT' => TokenType::RFC822_TEXT,
            'RFC822.HEAD' => TokenType::RFC822_HEAD,
            'UID' => TokenType::UID,
            default => match (true) {
                is_numeric($value) => TokenType::NUMBER,
                ctype_alnum($value) => TokenType::ALPHANUMERIC,
                ctype_cntrl($value) => TokenType::CTL,
                default => TokenType::UNKNOWN,
            },
        };
    }
}