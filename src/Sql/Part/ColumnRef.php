<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function is_string;
use function stripos;

/**
 * Normalized column reference for SELECT column lists.
 * Type discrimination happens at construction time, not at render time.
 *
 * Alias rules:
 * - $alias is set      → always render AS $alias
 * - $isStar            → no alias
 * - $containsAlias     → expression already has AS in its spec, no extra alias
 * - expression w/o alias → auto-generate alias as Expression{N} at render time
 * - string w/o alias   → $alias is set to the column name itself (auto-alias)
 */
final readonly class ColumnRef
{
    public ArgumentInterface $arg;
    public ?string $alias;
    public bool $isStar;
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
        // Star column — no alias
        if ($column === $star) {
            $this->arg = new Literal('*');
            $this->alias = null;
            $this->isStar = true;
            $this->containsAlias = false;
            return;
        }

        $this->isStar = false;

        // Normalize column to ArgumentInterface
        if ($column instanceof ExpressionInterface) {
            $this->arg = new SelectArgument($column);
        } else {
            $this->arg = new Identifier($column);
        }

        // Explicit alias from string key
        if (is_string($key)) {
            $this->alias = $key;
            $this->containsAlias = false;
            return;
        }

        // Expression at integer key — check if it already contains ' as '
        if ($column instanceof ExpressionInterface) {
            $data = $column->getExpressionData();
            $this->containsAlias = stripos($data['spec'], ' as ') !== false;
            $this->alias = null;
            return;
        }

        // String column at integer key — auto-alias with the column name itself
        $this->alias = $column;
        $this->containsAlias = false;
    }
}
