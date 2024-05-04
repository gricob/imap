<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol;

final class TagGenerator
{
    private const MAX_NUMBER = 999;
    private const NUMBER_PART_LENGTH = 3;
    private const INITIAL_LETTER = 'A';
    private const INITIAL_NUMBER = 0;

    private string $letter = self::INITIAL_LETTER;
    private int $number = self::INITIAL_NUMBER;

    public function next(): string
    {
        $this->number += 1;

        if ($this->number > self::MAX_NUMBER) {
            $this->letter++;
            $this->number = self::INITIAL_NUMBER;
        }

        if (strlen($this->letter) > 1) {
            $this->letter = self::INITIAL_LETTER;
        }

        return sprintf(
            '%s%s',
            $this->letter,
            str_pad((string) $this->number, self::NUMBER_PART_LENGTH, '0', STR_PAD_LEFT)
        );
    }
}