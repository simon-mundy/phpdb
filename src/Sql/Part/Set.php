<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Sql\Argument\NullValue;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;

use function implode;
use function is_string;
use function str_replace;

class Set extends AbstractPart
{
    /** @var array<string, ArgumentInterface>|null */
    public ?array $model = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->model === null || $this->model === []) {
            return null;
        }

        $setSql      = [];
        $i           = 0;
        $pi          = 0;
        $isPdoDriver = $renderer->driver instanceof PdoDriverInterface;

        foreach ($this->model as $column => $arg) {
            $prefix = $renderer->qo . str_replace('.', $renderer->qs, $column) . $renderer->qc . ' = ';

            $setSql[] = $prefix . match ($arg->getType()) {
                ArgumentType::Parameter => $renderer->bindParameter(
                    $arg,
                    $isPdoDriver ? 'c_' . $i++ : null
                ),
                ArgumentType::Select  => $renderer->render($arg->getValue()),
                ArgumentType::Literal => $arg->getValue(),
                ArgumentType::Null    => 'NULL',
                default               => $renderer->platform->quoteValue((string) $arg->getValue()),
            };
        }

        return 'SET ' . implode(', ', $setSql);
    }

    public function isEmpty(): bool
    {
        return $this->model === null || $this->model === [];
    }

    // phpcs:ignore Generic.NamingConventions.ConstructorName
    public function set(array $values, string|int $flag = 'set'): static
    {
        $this->model ??= [];

        if ($flag === 'set') {
            $this->model = [];
        }

        foreach ($values as $k => $v) {
            if (! is_string($k)) {
                throw new Exception\InvalidArgumentException('set() expects a string for the value key');
            }

            $this->model[$k] = $this->normalizeValue($k, $v);
        }
        return $this;
    }

    public function toArray(): array
    {
        if ($this->model === null) {
            return [];
        }

        $result = [];
        foreach ($this->model as $column => $arg) {
            $result[$column] = $arg->getValue();
        }
        return $result;
    }

    public function __clone()
    {
    }

    private function normalizeValue(string $column, mixed $value): ArgumentInterface
    {
        if ($value instanceof ArgumentInterface) {
            return $value;
        }

        if ($value instanceof Select) {
            return new SelectArgument($value);
        }

        if ($value instanceof ExpressionInterface) {
            return new SelectArgument($value);
        }

        if ($value === null) {
            return new NullValue();
        }

        return new Parameter($value, preferredName: $column);
    }
}
