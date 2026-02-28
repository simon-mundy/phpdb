<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function current;
use function get_debug_type;
use function is_array;
use function is_string;
use function key;
use function sprintf;

final readonly class JoinSpec
{
    public string|TableIdentifier|Select|ExpressionInterface $table;
    public JoinTableType $tableType;
    public ?string $alias;
    public PredicateInterface|string $on;
    public bool $isExpressionOn;
    public string $type;

    /** @var ColumnRef[] */
    public array $columnRefs;

    /** @param array{name: array|string|TableIdentifier, on: PredicateInterface|string, columns: array, type: string} $join */
    public function __construct(array $join)
    {
        $name = $join['name'];

        if (is_array($name)) {
            $this->alias = key($name);
            $table       = current($name);
        } else {
            $this->alias = null;
            $table       = $name;
        }

        $this->table     = $table;
        $this->tableType = match (true) {
            $table instanceof Expression       => JoinTableType::Expression,
            $table instanceof TableIdentifier  => JoinTableType::TableIdentifier,
            $table instanceof Select           => JoinTableType::Select,
            is_string($table)                  => JoinTableType::Identifier,
            default => throw new Exception\InvalidArgumentException(sprintf(
                'Join name expected to be Expression|TableIdentifier|Select|string, "%s" given',
                get_debug_type($table)
            )),
        };

        $on                   = $join['on'];
        $this->on             = $on;
        $this->isExpressionOn = $on instanceof ExpressionInterface;
        $this->type           = $join['type'];

        $refs = [];
        foreach ($join['columns'] as $key => $column) {
            $refs[] = new ColumnRef($key, $column);
        }
        $this->columnRefs = $refs;
    }
}
