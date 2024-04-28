<?php

namespace Gricob\IMAP\Mime\Part;

abstract readonly class Part
{
    public string $type;
    public string $subtype;
    public array $attributes;

    public function __construct(
        string $type,
        string $subtype,
        array $attributes,
    ) {
        $this->subtype = strtolower($subtype);
        $this->type = strtolower($type);
        $this->attributes = $attributes;
    }

    abstract public function findPartByMimeType(string $mimeType): ?SinglePart;

    public function mimeType(): string
    {
        return $this->type.'/'.$this->subtype;
    }
}