<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use Laminas\Stdlib\PriorityList;
use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Exception;

use function implode;
use function is_numeric;
use function is_scalar;
use function is_string;

/**
 * Holds SET column=value pairs for UPDATE statements and renders them.
 */
class Set extends AbstractPart
{
    private ?PriorityList $set = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        $set = $this->getModel();
        if ($set->count() === 0) {
            return null;
        }

        $setSql      = [];
        $i           = 0;
        $isPdoDriver = $processor->driver instanceof PdoDriverInterface;

        foreach ($set as $column => $value) {
            $prefix  = $processor->resolveColumnValue(
                [
                    'column'       => $column,
                    'fromTable'    => '',
                    'isIdentifier' => true,
                ],
                'column'
            );
            $prefix .= ' = ';

            if (is_scalar($value) && $processor->parameterContainer) {
                // use incremental value instead of column name for PDO
                // @see https://github.com/zendframework/zend-db/issues/35
                $paramName = $column;
                if ($isPdoDriver) {
                    $paramName = 'c_' . $i++;
                }

                $setSql[] = $prefix . $processor->driver->formatParameterName($paramName);
                $processor->parameterContainer->offsetSet($paramName, $value);
            } else {
                $setSql[] = $prefix . $processor->resolveColumnValue($value);
            }
        }

        return 'SET ' . implode(', ', $setSql);
    }

    public function isEmpty(): bool
    {
        return $this->set === null || $this->set->count() === 0;
    }

    /**
     * Set key/value pairs.
     *
     * @param string|int $flag One of VALUES_SET, VALUES_MERGE, or a numeric priority
     * @throws Exception\InvalidArgumentException
     */
    public function set(array $values, string|int $flag = 'set'): void
    {
        $set = $this->getModel();
        if ($flag === 'set') {
            $set->clear();
        }

        $priority = is_numeric($flag) ? $flag : 0;
        foreach ($values as $k => $v) {
            if (! is_string($k)) {
                throw new Exception\InvalidArgumentException('set() expects a string for the value key');
            }

            $set->insert($k, $v, $priority);
        }
    }

    /**
     * Get the underlying PriorityList model, creating it lazily.
     */
    public function getModel(): PriorityList
    {
        if ($this->set === null) {
            $this->set = new PriorityList();
            $this->set->isLIFO(false);
        }
        return $this->set;
    }

    /**
     * Get the set values as an array.
     */
    public function toArray(): array
    {
        return $this->getModel()->toArray();
    }

    public function __clone()
    {
        if ($this->set !== null) {
            $this->set = clone $this->set;
        }
    }
}
