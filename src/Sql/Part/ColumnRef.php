<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function is_string;
use function stripos;

/**
 * Normalized column reference for SELECT column lists.
 * Type discrimination happens at construction time, not at render time.
 *
 * Argument types determine rendering:
 * - Literal       → star column (*), prefixed, no alias
 * - Identifier    → column name, prefixed, alias handling
 * - SelectArgument → expression/subselect, alias handling
 *
 * Alias rules:
 * - $alias is set      → always render AS $alias
 * - $containsAlias     → expression already has AS in its spec, no extra alias
 * - expression w/o alias → auto-generate alias as Expression{N} at render time
 * - string w/o alias   → $alias is set to the column name itself (auto-alias)
 */
final readonly class ColumnRef
{
    public ArgumentInterface $arg;
    public ?string $alias;
    public bool $containsAlias;

    /**
     * @param int|string                 $key    Array key (int = auto-alias, string = explicit alias)
     * @param ExpressionInterface|string $column The column value
     * @param string                     $star   The SQL_STAR constant value
     */
    public function __construct(
        int|string $key,
        ExpressionInterface|string $column,
        string $star = Select::SQL_STAR,
    ) {
        if ($column === $star) {
            $this->arg           = new Literal('*');
            $this->alias         = null;
            $this->containsAlias = false;
            return;
        }

        if ($column instanceof ExpressionInterface) {
            $this->arg = new SelectArgument($column);
        } else {
            $this->arg = new Identifier($column);
        }

        if (is_string($key)) {
            $this->alias         = $key;
            $this->containsAlias = false;
            return;
        }

        if ($column instanceof ExpressionInterface) {
            $this->containsAlias = $column instanceof Expression
                && stripos($column->getExpression(), ' as ') !== false;
            $this->alias         = null;
            return;
        }

        $this->alias         = $column;
        $this->containsAlias = false;
    }
}
