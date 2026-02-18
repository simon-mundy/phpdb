<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\ExpressionInterface;

use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_split;
use function str_contains;

/**
 * Holds and renders ORDER BY clause.
 * Normalizes order specs to OrderSpec at add time.
 */
class OrderBy extends AbstractPart
{
    public const ORDER_ASCENDING  = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    /** @var OrderSpec[] */
    private array $order = [];

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->order === []) {
            return null;
        }

        $orders = [];
        foreach ($this->order as $spec) {
            $orders[] = match ($spec->column->getType()) {
                ArgumentType::Select     => $processor->processExpression($spec->column->getValue()),
                ArgumentType::Identifier => $processor->platform->quoteIdentifierInFragment($spec->column->getValue())
                                             . ' ' . $spec->direction,
            };
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    public function isEmpty(): bool
    {
        return $this->order === [];
    }

    public function add(ExpressionInterface|array|string $order): void
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
                // ['column' => 'DESC']
                $this->order[] = new OrderSpec($k, $v);
            } elseif (str_contains($v, ' ')) {
                // 'column DESC'
                [$col, $dir] = explode(' ', $v, 2);
                $this->order[] = new OrderSpec($col, $dir);
            } else {
                // 'column' (no direction)
                $this->order[] = new OrderSpec($v);
            }
        }
    }

    /**
     * Reconstruct the order as 'column DIRECTION' strings for getRawState() compatibility.
     * Expressions are returned at integer keys.
     */
    public function get(): array
    {
        $result = [];
        foreach ($this->order as $spec) {
            if ($spec->column->getType() === ArgumentType::Select) {
                $result[] = $spec->column->getValue();
            } else {
                $result[] = $spec->column->getValue() . ' ' . $spec->direction;
            }
        }
        return $result;
    }

    public function reset(): void
    {
        $this->order = [];
    }
}
