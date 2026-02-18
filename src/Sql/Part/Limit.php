<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;

/**
 * Holds and renders LIMIT clause.
 */
class Limit extends AbstractPart
{
    private string|int|null $limit = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->limit === null) {
            return null;
        }

        if ($processor->parameterContainer instanceof ParameterContainer) {
            $paramPrefix = $processor->getParamPrefix();
            $processor->parameterContainer->offsetSet(
                $paramPrefix . 'limit',
                $this->limit,
                ParameterContainer::TYPE_INTEGER
            );
            return 'LIMIT ' . $processor->driver->formatParameterName($paramPrefix . 'limit');
        }

        return 'LIMIT ' . $processor->platform->quoteValue((string) $this->limit);
    }

    public function isEmpty(): bool
    {
        return $this->limit === null;
    }

    public function set(string|int|null $limit): void
    {
        $this->limit = $limit;
    }

    public function get(): string|int|null
    {
        return $this->limit;
    }
}
