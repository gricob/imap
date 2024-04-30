<?php

namespace Gricob\IMAP\Protocol\Response\Line\Data\Item\BodyStructure;

final readonly class SinglePart extends Part
{
    private const TEXT_PATTERN = '/^\(\"(?<type>TEXT)\" \"(?<subtype>.*?)\" \((?<attributes>.*?)\) (NIL|\"(?<id>.*?)\") (NIL|\"(?<description>.*)\") \"(?<encoding>.*?)\" (?<size>\d+) (?<bodyLines>\d+) (NIL|\"(?<md5>.*?)\") (NIL|\(\"(?<disposition>.*?)\" (NIL|\((?<dispositionAttributes>.*?)\)))/';
    private const PATTERN = '/^\(\"(?<type>.*?)\" \"(?<subtype>.*?)\" \((?<attributes>.*?)\) (NIL|\"(?<id>.*?)\") (NIL|\"(?<description>.*)\") \"(?<encoding>.*?)\" (?<size>\d+) (NIL|\"(?<md5>.*?)\") (NIL|\(\"(?<disposition>.*?)\" (NIL|\((?<dispositionAttributes>.*?)\)))/';

    /**
     * @param array<string,string> $attributes
     * @param array<string,string> $dispositionAttributes
     */
    public function __construct(
        string $type,
        string $subtype,
        array $attributes,
        public ?string $id,
        public ?string $description,
        public string $encoding,
        public int $size,
        public ?string $md5,
        public ?string $disposition,
        public ?array $dispositionAttributes,
    ) {
        parent::__construct($type, $subtype, $attributes);
    }

    public static function tryParse(string $raw): ?static
    {
        if (
            !preg_match(self::TEXT_PATTERN, $raw, $matches)
            && !preg_match(self::PATTERN, $raw, $matches)
        ) {
            return null;
        }

        return new self(
            $matches['type'],
            $matches['subtype'],
            self::tryParseAttributes($matches['attributes']) ?? [],
            empty($matches['id']) ?  null : $matches['id'],
            empty($matches['description']) ? null : $matches['description'],
            $matches['encoding'],
            (int) $matches['size'],
            empty($matches['md5']) ? null : $matches['md5'],
            empty($matches['disposition']) ? null : $matches['disposition'],
            self::tryParseAttributes($matches['dispositionAttributes'] ?? null),

        );
    }
}