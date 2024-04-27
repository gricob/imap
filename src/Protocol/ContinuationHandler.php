<?php

namespace Gricob\IMAP\Protocol;

interface ContinuationHandler
{
    public function continue(): void;
}