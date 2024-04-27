<?php

namespace Gricob\IMAP\Protocol\Command;

interface Continuable
{
    public function continue(): string;
}