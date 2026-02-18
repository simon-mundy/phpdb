<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

/**
 * Discriminator for join table variants.
 * Set at JoinSpec construction time so the render loop uses a match
 * instead of an instanceof chain.
 */
enum JoinTableType
{
    case Expression;
    case TableIdentifier;
    case Select;
    case Identifier;
}
