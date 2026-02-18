<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function is_array;
use function key;
use function strtoupper;

/**
 * Normalized join specification.
 * All type discrimination (array alias, table type, expression ON, direction normalization)
 * happens at construction time.
 */
final readonly class JoinSpec
{
    public string|TableIdentifier|Select|ExpressionInterface $table;
    public ?string $alias;
    public PredicateInterface|string $on;
    public bool $isExpressionOn;
    public string $type;
    public array $columns;

    /**
     * @param array{name: array|string|TableIdentifier, on: PredicateInterface|string, columns: array, type: string} $join
     */
    public function __construct(array $join)
    {
        $name = $join['name'];

        if (is_array($name)) {
            $this->alias = key($name);
            $this->table = current($name);
        } else {
            $this->alias = null;
            $this->table = $name;
        }

        $this->on             = $join['on'];
        $this->isExpressionOn = $join['on'] instanceof ExpressionInterface;
        $this->type           = strtoupper($join['type']);
        $this->columns        = $join['columns'];
    }
}
