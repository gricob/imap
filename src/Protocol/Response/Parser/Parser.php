<?php

namespace Gricob\IMAP\Protocol\Response\Parser;

use DateTimeImmutable;
use Doctrine\Common\Lexer\Token;
use Gricob\IMAP\Protocol\Response\Line\CommandContinuation;
use Gricob\IMAP\Protocol\Response\Line\Data\CapabilityData;
use Gricob\IMAP\Protocol\Response\Line\Data\ExistsData;
use Gricob\IMAP\Protocol\Response\Line\Data\ExpungeData;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\Address;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodySection;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\BodyStructure;
use Gricob\IMAP\Protocol\Response\Line\Data\Fetch\Envelope;
use Gricob\IMAP\Protocol\Response\Line\Data\FetchData;
use Gricob\IMAP\Protocol\Response\Line\Data\FlagsData;
use Gricob\IMAP\Protocol\Response\Line\Data\ListData;
use Gricob\IMAP\Protocol\Response\Line\Data\RecentData;
use Gricob\IMAP\Protocol\Response\Line\Data\SearchData;
use Gricob\IMAP\Protocol\Response\Line\Line;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\AppendUidCode;
use Gricob\IMAP\Protocol\Response\Line\Status\Code\Code;
use Gricob\IMAP\Protocol\Response\Line\Status\Status;
use Gricob\IMAP\Protocol\Response\Line\Status\StatusType;

readonly class Parser
{
    private Lexer $lexer;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    /**
     * @throws ParseError
     */
    public function parse(string $raw): Line
    {
        $this->lexer->setInput($raw);
        $this->lexer->moveNext();

        if ($this->lexer->isNextToken(TokenType::PLUS_SIGN)) {
            return $this->commandContinuation();
        }

        $tag = $this->getToken(TokenType::ASTERISK, TokenType::NUMBER, TokenType::ALPHANUMERIC)->value;
        $this->space();

        if ($this->lexer->isNextToken(TokenType::NUMBER)) {
            $value = $this->number();
            $this->space();

            return match ($this->lexer->lookahead?->type) {
                TokenType::EXISTS => $this->exists($value),
                TokenType::EXPUNGE => $this->expunge($value),
                TokenType::RECENT => $this->recent($value),
                TokenType::FETCH => $this->fetch($value),
                default => throw new ParseError()
            };
        }

        return match ($this->lexer->lookahead?->type) {
            TokenType::STATUS => $this->status($tag),
            TokenType::CAPABILITY => $this->capability(),
            TokenType::LIST => $this->list(),
            TokenType::FLAGS => $this->flags(),
            TokenType::SEARCH => $this->search(),
            default => throw new ParseError()
        };
    }

    /**
     * @throws ParseError
     */
    private function commandContinuation(): CommandContinuation
    {
        $this->getToken(TokenType::PLUS_SIGN);
        $message = '';

        if ($this->nextIsSpace()) {
            $this->space();

            $message = $this->getValueUntil(TokenType::CRLF);
        }

        return new CommandContinuation($message);
    }

    /**
     * @throws ParseError
     */
    private function status(string $tag): Status
    {
        $type = StatusType::from($this->getToken(TokenType::STATUS)->value);

        $code = null;
        $message = '';

        if ($this->nextIsSpace()) {
            $this->space();

            if ($this->lexer->isNextToken(TokenType::OPEN_BRACKETS)) {
                $code = $this->statusCode();

                if ($this->nextIsSpace()) {
                    $this->space();
                }
            }

            $message = $this->getValueUntil(TokenType::CRLF);
        }

        return new Status($tag, $type, $code, $message);
    }

    /**
     * @throws ParseError
     */
    private function statusCode(): ?Code
    {
        $this->getToken(TokenType::OPEN_BRACKETS);

        switch ($this->lexer->lookahead?->type) {
            case TokenType::APPENDUID:
                $code = $this->appendUidStatusCode();
                break;
            default:
                $this->getValueUntil(TokenType::CLOSE_BRACKETS);
                $code = null;
        }

        $this->getToken(TokenType::CLOSE_BRACKETS);

        return $code;
    }

    /**
     * @throws ParseError
     */
    private function appendUidStatusCode(): AppendUidCode
    {
        $this->getToken(TokenType::APPENDUID);
        $this->space();
        $uidValidity = $this->number();
        $this->space();
        $uid = (int) $this->getToken(TokenType::NUMBER)->value;

        return new AppendUidCode($uidValidity, $uid);
    }

    /**
     * @throws ParseError
     */
    private function capability(): CapabilityData
    {
        $this->getToken(TokenType::CAPABILITY);
        $capabilities = [];

        while ($this->nextIsSpace()) {
            $this->space();
            $capabilities[] = $this->atom();
        }

        return new CapabilityData($capabilities);
    }

    /**
     * @throws ParseError
     */
    private function list(): ListData
    {
        $this->getToken(TokenType::LIST);
        $this->space();

        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $attributes = [];
        while (!$this->lexer->isNextToken(TokenType::CLOSE_PARENTHESIS)) {
            $attributes[] = $this->getValueUntil(TokenType::SP, TokenType::CLOSE_PARENTHESIS);

            if ($this->nextIsSpace()) {
                $this->space();
            }
        }
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        $this->space();
        $hierarchy = $this->string();
        $this->space();
        $name = $this->astring();

        return new ListData($attributes, $hierarchy, $name);
    }

    /**
     * @throws ParseError
     */
    private function flags(): FlagsData
    {
        return new FlagsData($this->flagList());
    }

    /**
     * @throws ParseError
     */
    private function search(): SearchData
    {
        $this->getToken(TokenType::SEARCH);

        $numbers = [];
        while (!$this->lexer->isNextToken(TokenType::CRLF)) {
            if ($this->nextIsSpace()) {
                $this->space();
            }

            $numbers[] = $this->number();
        }

        return new SearchData($numbers);
    }

    /**
     * @throws ParseError
     */
    private function fetch(int $id): FetchData
    {
        $this->getToken(TokenType::FETCH);
        $this->space();
        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $flags = null;
        $internalDate = null;
        $envelope = null;
        $rfc822 = null;
        $rfc822Size = null;
        $uid = null;
        $bodyStructure = null;
        $bodySections = [];

        while (!$this->lexer->isNextToken(TokenType::CLOSE_PARENTHESIS)) {
            switch ($this->lexer->lookahead?->type) {
                case TokenType::FLAGS:
                    $flags = $this->flagList();
                    break;
                case TokenType::INTERNALDATE:
                    $this->getToken(TokenType::INTERNALDATE);
                    $this->space();
                    $internalDate = $this->dateTime();
                    break;
                case TokenType::UID:
                    $this->getToken(TokenType::UID);
                    $this->space();
                    $uid = $this->number();
                    break;
                case TokenType::RFC822_SIZE:
                    $this->getToken(TokenType::RFC822_SIZE);
                    $this->space();
                    $rfc822Size = $this->number();
                    break;
                case TokenType::BODY:
                    $this->getToken(TokenType::BODY);
                    if ($this->lexer->isNextToken(TokenType::OPEN_BRACKETS)) {
                        $this->getToken(TokenType::OPEN_BRACKETS);
                        $section = $this->getValueUntil(TokenType::CLOSE_BRACKETS);
                        $this->getToken(TokenType::CLOSE_BRACKETS);
                        $this->space();
                        $text = $this->literal();

                        $bodySections[] = new BodySection($section, $text);
                    }
                    break;
                case TokenType::ENVELOPE:
                    $this->getToken(TokenType::ENVELOPE);
                    $this->space();
                    $envelope = $this->envelope();
                    break;
                case TokenType::BODYSTRUCTURE:
                    $this->getToken(TokenType::BODYSTRUCTURE);
                    $this->space();
                    $bodyStructure = $this->bodyStructure();
                    break;
                default:
                    $this->getToken();
            }
        }

        return new FetchData(
            $id,
            $flags,
            $internalDate,
            $envelope,
            $rfc822Size,
            $rfc822,
            $uid,
            $bodyStructure,
            bodySections: $bodySections,
        );
    }

    /**
     * @throws ParseError
     */
    private function envelope(): Envelope
    {
        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $date = $this->envelopeDate();
        $this->space();
        $subject = match($this->lexer->lookahead?->type) {
            TokenType::OPEN_BRACES => $this->literal(),
            default => $this->nstring(),
        };
        $this->space();
        $from = $this->nullableAddressList();
        $this->space();
        $sender = $this->nullableAddressList();
        $this->space();
        $replyTo = $this->nullableAddressList();
        $this->space();
        $to = $this->nullableAddressList();
        $this->space();
        $cc = $this->nullableAddressList();
        $this->space();
        $bcc = $this->nullableAddressList();
        $this->space();
        $inReplyTo = $this->nstring();
        $this->space();
        $messageId = $this->nstring();
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return new Envelope(
            $date,
            $subject,
            $from,
            $sender,
            $replyTo,
            $to,
            $cc,
            $bcc,
            $inReplyTo,
            $messageId,
        );
    }

    /**
     * @throws ParseError
     */
    private function envelopeDate(): ?DateTimeImmutable
    {
        $value = $this->nstring();

        if (null === $value) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (\Exception) {
            $date = null;
        }

        return  $date ?: throw new ParseError('Unable to parse envelope date');
    }

    /**
     * @return Address[]|null
     * @throws ParseError
     */
    private function nullableAddressList(): ?array
    {
        if ($this->lexer->isNextToken(TokenType::NIL)) {
            return $this->nil();
        }

        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $addresses = [];
        while ($this->lexer->isNextToken(TokenType::OPEN_PARENTHESIS)) {
            $addresses[] = $this->address();
        }
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return $addresses;
    }

    /**
     * @throws ParseError
     */
    private function address(): Address
    {
        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $displayName = $this->nstring();
        $this->space();
        $atDomainList = $this->nstring();
        $this->space();
        $mailboxName = $this->nstring();
        $this->space();
        $hostname = $this->nstring();
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return new Address(
            $displayName,
            $atDomainList,
            $mailboxName,
            $hostname,
        );
    }

    /**
     * @throws ParseError
     */
    public function bodyStructure(): BodyStructure
    {
        $part = $this->part();

        return new BodyStructure($part);
    }

    /**
     * @throws ParseError
     */
    private function part(): BodyStructure\Part
    {
        return $this->lexer->glimpse()?->isA(TokenType::OPEN_PARENTHESIS)
            ? $this->multipart()
            : $this->simplePart();
    }

    /**
     * @throws ParseError
     */
    private function multipart(): BodyStructure\MultiPart
    {
        $parts = [];
        $disposition = null;
        $language = null;
        $location = null;

        $this->getToken(TokenType::OPEN_PARENTHESIS);

        while ($this->lexer->isNextToken(TokenType::OPEN_PARENTHESIS)) {
            $parts[] = $this->part();
        }

        $this->space();
        $subtype = $this->string();

        if ($this->nextIsSpace()) {
            $this->space();
            $attributes = $this->attributeValuePairs();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $disposition = $this->disposition();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $language = $this->bodyLanguage();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $location = $this->nstring();
        }

        $this->getValueUntil(TokenType::CLOSE_PARENTHESIS);
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return new BodyStructure\MultiPart(
            $subtype,
            $attributes ?? [],
            $parts,
            $disposition,
            $language,
            $location
        );
    }

    /**
     * @throws ParseError
     */
    private function simplePart(): BodyStructure\SinglePart
    {
        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $type = $this->quoted();
        $normalizedType = strtoupper($type);
        $this->space();
        $subtype = $this->quoted();
        $normalizedSubtype = strtoupper($subtype);
        $this->space();
        $attributes = $this->attributeValuePairs();
        $this->space();
        $id = $this->nstring();
        $this->space();
        $description = $this->nstring();
        $this->space();
        $encoding = $this->string();
        $this->space();
        $size = $this->number();

        $textLines = 0;
        $md5 = null;
        $disposition = null;
        $language = null;
        $location = null;

        $isTextPart = $normalizedType === 'TEXT';
        $isMessagePart = $normalizedType === 'MESSAGE' && $normalizedSubtype === 'RFC822';

        if ($isTextPart) {
            $this->space();
            $textLines = $this->number();
        }

        if ($isMessagePart) {
            $this->space();
            $envelope = $this->envelope();
            $this->space();
            $bodyStructure = $this->bodyStructure();
            $this->space();
            $textLines = $this->number();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $md5 = $this->nstring();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $disposition = $this->disposition();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $language = $this->bodyLanguage();
        }

        if ($this->nextIsSpace()) {
            $this->space();
            $location = $this->nstring();
        }

        $this->getValueUntil(TokenType::CLOSE_PARENTHESIS);
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        if ($isTextPart) {
            return new BodyStructure\TextPart(
                $subtype,
                $attributes,
                $id,
                $description,
                $encoding,
                $size,
                $textLines,
                $md5,
                $disposition,
                $language,
                $location,
            );
        }

        if ($isMessagePart) {
            return new BodyStructure\MessagePart(
                $attributes,
                $id,
                $description,
                $encoding,
                $size,
                $envelope,
                $bodyStructure,
                $textLines,
                $md5,
                $disposition,
                $language,
                $location,
            );
        }

        return new BodyStructure\SinglePart(
            $type,
            $subtype,
            $attributes,
            $id,
            $description,
            $encoding,
            $size,
            $md5,
            $disposition,
            $language,
            $location,
        );
    }

    /**
     * @return string[]|null
     * @throws ParseError
     */
    private function bodyLanguage(): ?array
    {
        if ($this->lexer->isNextToken(TokenType::OPEN_PARENTHESIS)) {
            $this->getToken(TokenType::OPEN_PARENTHESIS);
            $lang = [];
            while (!$this->lexer->isNextToken(TokenType::CLOSE_PARENTHESIS)) {
                $lang[] = $this->string();

                if ($this->nextIsSpace()) {
                    $this->space();
                }
            }

            $this->getToken(TokenType::CLOSE_PARENTHESIS);
            return $lang;
        }

        $lang = $this->nstring();

        return $lang ? [$lang] : null;
    }

    private function disposition(): ?BodyStructure\Disposition
    {
        if ($this->lexer->isNextToken(TokenType::NIL)) {
            return $this->nil();
        }

        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $type = $this->string();
        $this->space();
        $attributes = $this->lexer->isNextToken(TokenType::NIL)
            ? $this->nil()
            : $this->attributeValuePairs();
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return new BodyStructure\Disposition(
            $type,
            $attributes ?? []
        );
    }

    /**
     * @return array<string, string>
     * @throws ParseError
     */
    private function attributeValuePairs(): array
    {
        $values = [];
        if ($this->lexer->isNextToken(TokenType::NIL)) {
            $this->nil();
            return $values;
        }

        $this->getToken(TokenType::OPEN_PARENTHESIS);

        while (!$this->lexer->isNextToken(TokenType::CLOSE_PARENTHESIS)) {
            if ($this->nextIsSpace()) {
                $this->space();
            }

            $attribute = $this->quoted();
            $this->space();
            $value = $this->quoted();

            $values[$attribute] = $value;
        }
        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return $values;
    }

    /**
     * @throws ParseError
     */
    private function exists(int $numberOfMessages): ExistsData
    {
        $this->getToken(TokenType::EXISTS);

        return new ExistsData($numberOfMessages);
    }

    /**
     * @throws ParseError
     */
    private function expunge(int $id): ExpungeData
    {
        $this->getToken(TokenType::EXPUNGE);

        return new ExpungeData($id);
    }

    /**
     * @throws ParseError
     */
    private function recent(int $numberOfMessages): RecentData
    {
        $this->getToken(TokenType::RECENT);

        return new RecentData($numberOfMessages);
    }

    /**
     * @return string[]
     * @throws ParseError
     */
    private function flagList(): array
    {
        $flags = [];
        $this->getToken(TokenType::FLAGS);
        $this->space();
        $this->getToken(TokenType::OPEN_PARENTHESIS);
        $isFirstFlag = true;

        while (!$this->lexer->isNextToken(TokenType::CLOSE_PARENTHESIS)) {
            if (!$isFirstFlag) {
                $this->space();
            }

            $flags[] = $this->getValueUntil(TokenType::SP, TokenType::CLOSE_PARENTHESIS);

            $isFirstFlag = false;
        }

        $this->getToken(TokenType::CLOSE_PARENTHESIS);

        return $flags;
    }

    /**
     * @throws ParseError
     */
    private function dateTime(): DateTimeImmutable
    {
        $this->getToken(TokenType::DOUBLE_QUOTE);
        $value = $this->getValueUntil(TokenType::DOUBLE_QUOTE);
        $this->getToken(TokenType::DOUBLE_QUOTE);

        return DateTimeImmutable::createFromFormat('d-M-Y H:i:s O', $value)
            ?: throw new ParseError(sprintf('Invalid date time "%s"', $value));
    }

    /**
     * @throws ParseError
     */
    private function number(): int
    {
        return (int) $this->getToken(TokenType::NUMBER)->value;
    }

    /**
     * @throws ParseError
     */
    private function astring(): string
    {
        if ($this->lexer->isNextToken(TokenType::OPEN_BRACES)) {
            return $this->literal();
        }

        return $this->string();
    }

    /**
     * @throws ParseError
     */
    private function nstring(): ?string
    {
        if ($this->lexer->isNextToken(TokenType::NIL)) {
            return $this->nil();
        }

        return $this->string();
    }

    /**
     * @throws ParseError
     */
    public function atom(): string
    {
        return $this->getValueUntil(
            TokenType::OPEN_PARENTHESIS,
            TokenType::CLOSE_PARENTHESIS,
            TokenType::OPEN_BRACES,
            TokenType::CTL,
            TokenType::SP,
            TokenType::CRLF,
            TokenType::DOUBLE_QUOTE,
            TokenType::BACKSLASH,
            TokenType::ASTERISK,
            TokenType::PERCENT_SIGN,
        );
    }

    /**
     * @throws ParseError
     */
    private function string(): string
    {
        return match($this->lexer->lookahead?->type) {
            TokenType::DOUBLE_QUOTE => $this->quoted(),
            default => $this->atom()
        };
    }

    /**
     * @throws ParseError
     */
    private function quoted(): string
    {
        $this->getToken(TokenType::DOUBLE_QUOTE);
        $value = '';

        while (!$this->lexer->isNextToken(TokenType::DOUBLE_QUOTE)) {
            if ($this->lexer->isNextToken(TokenType::BACKSLASH)) {
                $value .= $this->quotedSpecials();
            } else {
                $value .= $this->getToken()->value;
            }
        }

        $this->getToken(TokenType::DOUBLE_QUOTE);

        return $value;
    }

    /**
     * @throws ParseError
     */
    private function quotedSpecials(): string
    {
        $this->getToken(TokenType::BACKSLASH);

        if ($this->lexer->isNextToken(TokenType::DOUBLE_QUOTE)) {
            $this->getToken(TokenType::DOUBLE_QUOTE);
            return "\"";
        }

        return "\\";
    }

    /**
     * @throws ParseError
     */
    private function literal(): string
    {
        $this->getToken(TokenType::OPEN_BRACES);
        $size = (int) $this->getToken(TokenType::NUMBER)->value;
        $this->getToken(TokenType::CLOSE_BRACES);
        $this->getToken(TokenType::CRLF);

        $value = '';
        while (strlen($value) < $size) {
            $value .= $this->getToken()->value;
        }

        return $value;
    }

    /**
     * @throws ParseError
     */
    private function nil(): null
    {
        $this->getToken(TokenType::NIL);

        return null;
    }

    /**
     * @return Token<TokenType, string>
     * @throws ParseError
     */
    private function getToken(TokenType ...$expected): Token
    {
        if (!empty($expected) && !in_array($this->lexer->lookahead?->type, $expected)) {
            $position = $this->lexer->lookahead?->position;

            throw ParseError::unexpectedToken(
                $this->lexer->lookahead?->type,
                $expected,
                $position ? $this->lexer->getInputUntilPosition($position) : ''
            );
        }

        $this->lexer->moveNext();

        return $this->lexer->token ?? throw new ParseError();
    }

    private function nextIsSpace(): bool
    {
        return $this->lexer->lookahead?->isA(TokenType::SP) ?? false;
    }

    /**
     * @throws ParseError
     */
    private function space(): void
    {
        $this->getToken(TokenType::SP);
    }

    /**
     * @throws ParseError
     */
    private function getValueUntil(TokenType ...$types): string
    {
        $value = '';

        while (!in_array($this->lexer->lookahead?->type, $types)) {
            $value .= $this->getToken()->value;
        }

        return $value;
    }
}