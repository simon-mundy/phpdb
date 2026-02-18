<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Laminas\Stdlib\PriorityList;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Select;

use function implode;
use function is_numeric;
use function is_string;

/**
 * Holds SET column=value pairs for UPDATE statements and renders them.
 * Normalizes values to ArgumentInterface at set time.
 */
class Set extends AbstractPart
{
    public ?PriorityList $model = null;

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->model === null || $this->model->count() === 0) {
            return null;
        }

        $setSql      = [];
        $i           = 0;
        $isPdoDriver = $processor->driver instanceof PdoDriverInterface;

        foreach ($this->model as $column => $arg) {
            /** @var ArgumentInterface $arg */
            $prefix = $processor->platform->quoteIdentifierInFragment($column) . ' = ';

            $setSql[] = $prefix . match ($arg->getType()) {
                ArgumentType::Parameter => $processor->renderParameter(
                    $arg,
                    $isPdoDriver ? 'c_' . $i++ : null
                ),
                ArgumentType::Select  => $processor->renderExpression($arg->getValue()),
                ArgumentType::Literal => $arg->getValue(),
                default               => $processor->platform->quoteValue((string) $arg->getValue()),
            };
        }

        return 'SET ' . implode(', ', $setSql);
    }

    public function isEmpty(): bool
    {
        return $this->model === null || $this->model->count() === 0;
    }

    /**
     * Set key/value pairs. Values are normalized to ArgumentInterface.
     *
     * @param string|int $flag One of VALUES_SET, VALUES_MERGE, or a numeric priority
     * @throws Exception\InvalidArgumentException
     */
    public function set(array $values, string|int $flag = 'set'): void
    {
        $this->initModel();

        if ($flag === 'set') {
            $this->model->clear();
        }

        $priority = is_numeric($flag) ? $flag : 0;
        foreach ($values as $k => $v) {
            if (! is_string($k)) {
                throw new Exception\InvalidArgumentException('set() expects a string for the value key');
            }

            $this->model->insert($k, $this->normalizeValue($k, $v), $priority);
        }
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
            /** @var ArgumentInterface $arg */
            $result[$column] = $arg->getValue();
        }
        return $result;
    }

    public function __clone()
    {
        if ($this->model !== null) {
            $this->model = clone $this->model;
        }
    }

    private function initModel(): void
    {
        if ($this->model === null) {
            $this->model = new PriorityList();
            $this->model->isLIFO(false);
        }
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
            return new Literal('NULL');
        }

        // Scalar — bind as parameter
        return new Parameter($value, preferredName: $column);
    }
}
