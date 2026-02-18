<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Parameter;

/**
 * Holds and renders OFFSET clause.
 * Normalizes offset value to Parameter at set time.
 */
class Offset extends AbstractPart
{
    private ?Parameter $offset = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->offset === null) {
            return null;
        }

        return 'OFFSET ' . $processor->renderParameter($this->offset);
    }

    public function isEmpty(): bool
    {
        return $this->offset === null;
    }

    public function set(string|int|null $offset): void
    {
        $this->offset = $offset === null
            ? null
            : new Parameter($offset, preferredName: 'offset', typeHint: ParameterContainer::TYPE_INTEGER);
    }

    public function get(): string|int|null
    {
        return $this->offset?->getValue();
    }
}
