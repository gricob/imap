<?php

declare(strict_types=1);

namespace Gricob\IMAP\Protocol\Command\Authenticate;

use Gricob\IMAP\Protocol\Command\Argument\Argument;
use Gricob\IMAP\Protocol\Command\Continuable;

interface SASLMechanism extends Argument, Continuable
{
}