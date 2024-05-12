<?php

namespace Gricob\IMAP\Protocol\Response\Parser;

enum TokenType
{
    case SP;
    case DOT;
    case ASTERISK;
    case PERCENT_SIGN;
    case PLUS_SIGN;
    case EQUALS_SIGN;
    case DOUBLE_QUOTE;
    case NUMBER;
    case ALPHANUMERIC;
    case NIL;
    case OPEN_BRACKETS;
    case CLOSE_BRACKETS;
    case OPEN_BRACES;
    case CLOSE_BRACES;
    case OPEN_PARENTHESIS;
    case CLOSE_PARENTHESIS;
    case BACKSLASH;
    case CRLF;
    case CTL;

    case STATUS;

    case APPENDUID;

    case CAPABILITY;
    case LIST;
    case FLAGS;
    case INTERNALDATE;
    case RECENT;
    case FETCH;
    case SEARCH;
    case EXISTS;
    case EXPUNGE;
    case BODY;
    case BODYSTRUCTURE;
    case ENVELOPE;
    case RFC822;
    case RFC822_SIZE;
    case RFC822_HEAD;
    case RFC822_TEXT;
    case UID;

    case UNKNOWN;
}