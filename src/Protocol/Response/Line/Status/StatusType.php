<?php

namespace Gricob\IMAP\Protocol\Response\Line\Status;

enum StatusType: string
{
    case OK = 'OK';
    case NO = 'NO';
    case BAD = 'BAD';
    case PREAUTH = 'PREAUTH';
    case BYE = 'BYE';
}