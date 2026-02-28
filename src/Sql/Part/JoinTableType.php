<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

enum JoinTableType
{
    case Expression;
    case TableIdentifier;
    case Select;
    case Identifier;
}
