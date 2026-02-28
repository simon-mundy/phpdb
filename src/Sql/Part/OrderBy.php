<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;

use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_split;
use function str_contains;

class OrderBy extends AbstractPart
{
    final public const ORDER_ASCENDING  = 'ASC';
    final public const ORDER_DESCENDING = 'DESC';

    /** @var OrderSpec[] */
    private array $order = [];

    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->order === []) {
            return null;
        }

        $platform = $processor->platform;
        $orders   = [];

        foreach ($this->order as $spec) {
            $column = $spec->column;

            if (is_string($column)) {
                $parts    = explode('.', $column, 2);
                $orders[] = $platform->quoteIdentifier($parts[0], $parts[1] ?? null)
                           . ' ' . $spec->direction;
            } else {
                $orders[] = $processor->renderExpression($column);
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    public function isEmpty(): bool
    {
        return $this->order === [];
    }

    public function add(ExpressionInterface|array|string $order): static
    {
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
                $this->order[] = new OrderSpec($col, $dir);
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
                $result[] = $spec->column . ' ' . $spec->direction;
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
