<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\ExpressionInterface;

use function explode;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function str_contains;
use function strcasecmp;
use function trim;

/**
 * Holds and renders ORDER BY clause.
 */
class OrderBy extends AbstractPart
{
    public const ORDER_ASCENDING  = 'ASC';
    public const ORDER_DESCENDING = 'DESC';

    private array $order = [];

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->order === []) {
            return null;
        }

        $orders = [];
        foreach ($this->order as $k => $v) {
            if ($v instanceof ExpressionInterface) {
                $orders[] = $processor->processExpression($v);
                continue;
            }

            if (is_int($k)) {
                if (str_contains($v, ' ')) {
                    [$k, $v] = explode(' ', $v, 2);
                } else {
                    $k = $v;
                    $v = self::ORDER_ASCENDING;
                }
            }

            if (strcasecmp(trim($v), self::ORDER_DESCENDING) === 0) {
                $orders[] = $processor->platform->quoteIdentifierInFragment($k) . ' ' . self::ORDER_DESCENDING;
            } else {
                $orders[] = $processor->platform->quoteIdentifierInFragment($k) . ' ' . self::ORDER_ASCENDING;
            }
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
            if (is_string($k)) {
                $this->order[$k] = $v;
            } else {
                $this->order[] = $v;
            }
        }
    }

    public function get(): array
    {
        return $this->order;
    }

    public function reset(): void
    {
        $this->order = [];
    }
}
