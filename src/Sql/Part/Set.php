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
use PhpDb\Sql\Select;

use function implode;
use function is_string;

/**
 * Holds SET column=value pairs for UPDATE statements and renders them.
 * Normalizes values to ArgumentInterface at set time.
 */
class Set extends AbstractPart
{
    /** @var array<string, ArgumentInterface>|null */
    public ?array $model = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->model === null || $this->model === []) {
            return null;
        }

        $setSql      = [];
        $i           = 0;
        $isPdoDriver = $processor->driver instanceof PdoDriverInterface;

        foreach ($this->model as $column => $arg) {
            $prefix = $processor->platform->quoteIdentifierInFragment($column) . ' = ';

            $setSql[] = $prefix . match ($arg->getType()) {
                ArgumentType::Parameter => $processor->renderParameter(
                    $arg,
                    $isPdoDriver ? 'c_' . $i++ : null
                ),
                ArgumentType::Select  => $processor->renderExpression($arg->getValue()),
                ArgumentType::Literal => $arg->getValue(),
                ArgumentType::Null    => 'NULL',
                default               => $processor->platform->quoteValue((string) $arg->getValue()),
            };
        }

        return 'SET ' . implode(', ', $setSql);
    }

    public function isEmpty(): bool
    {
        return $this->model === null || $this->model === [];
    }

    /**
     * Set key/value pairs. Values are normalized to ArgumentInterface.
     *
     * @param string|int $flag One of VALUES_SET, VALUES_MERGE, or a numeric priority
     * @throws Exception\InvalidArgumentException
     */
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

    /**
     * Reconstruct the raw values for getRawState() compatibility.
     */
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
        // Plain array of immutable value objects — no deep clone needed
    }

    /**
     * Normalize a raw value to an ArgumentInterface.
     */
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

        // Scalar — bind as parameter
        return new Parameter($value, preferredName: $column);
    }
}
