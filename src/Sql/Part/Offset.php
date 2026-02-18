<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;

/**
 * Holds and renders OFFSET clause.
 */
class Offset extends AbstractPart
{
    private string|int|null $offset = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->offset === null) {
            return null;
        }

        if ($processor->parameterContainer instanceof ParameterContainer) {
            $paramPrefix = $processor->getParamPrefix();
            $processor->parameterContainer->offsetSet(
                $paramPrefix . 'offset',
                $this->offset,
                ParameterContainer::TYPE_INTEGER
            );
            return 'OFFSET ' . $processor->driver->formatParameterName($paramPrefix . 'offset');
        }

        return 'OFFSET ' . $processor->platform->quoteValue((string) $this->offset);
    }

    public function isEmpty(): bool
    {
        return $this->offset === null;
    }

    public function set(string|int|null $offset): void
    {
        $this->offset = $offset;
    }

    public function get(): string|int|null
    {
        return $this->offset;
    }
}
