<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Part\OrderSpec;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

use function explode;
use function implode;
use function is_array;
use function is_string;
use function ltrim;
use function preg_split;
use function str_contains;
use function str_replace;

class OrderBy extends AbstractClause
{
    final public const ORDER_ASCENDING  = 'ASC';
    final public const ORDER_DESCENDING = 'DESC';

    /** @var OrderSpec[] */
    private array $order = [];

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->order === []) {
            return null;
        }

        $orders = [];

        foreach ($this->order as $spec) {
            $column = $spec->column;

            if ($column instanceof Identifier) {
                $orders[] = $renderer->qo
                    . str_replace('.', $renderer->qs, $column->identifier)
                    . $renderer->qc . ' ' . $spec->direction;
            } elseif ($column instanceof ArgumentInterface) {
                $pi       = 0;
                $orders[] = $renderer->renderArgument($column, '', $pi)
                           . ' ' . $spec->direction;
            } else {
                $orders[] = $renderer->render($column);
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    public function isEmpty(): bool
    {
        return $this->order === [];
    }

    public function add(ArgumentInterface|ExpressionInterface|array|string $order): static
    {
        if ($order instanceof ArgumentInterface) {
            $this->order[] = new OrderSpec($order);
            return $this;
        }

        if (is_string($order)) {
            $order = str_contains($order, ',') ? preg_split('#,\s+#', $order) : (array) $order;
        } elseif (! is_array($order)) {
            $order = [$order];
        }

        foreach ($order as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $this->order[] = new OrderSpec($v);
            } elseif (is_string($k)) {
                $this->order[] = new OrderSpec($k, $v);
            } elseif (str_contains($v, ' ')) {
                [$col, $dir]   = explode(' ', $v, 2);
                $this->order[] = new OrderSpec($col, ltrim($dir));
            } else {
                $this->order[] = new OrderSpec($v);
            }
        }
        return $this;
    }

    public function get(): array
    {
        $result = [];
        foreach ($this->order as $spec) {
            if ($spec->column instanceof ExpressionInterface) {
                $result[] = $spec->column;
            } else {
                $result[] = $spec->column->getValue() . ' ' . $spec->direction;
            }
        }
        return $result;
    }

    public function reset(): static
    {
        $this->order = [];
        return $this;
    }
}
