<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Parameter;

/**
 * Holds and renders LIMIT clause.
 * Normalizes limit value to Parameter at set time.
 */
class Limit extends AbstractPart
{
    private ?Parameter $limit = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->limit === null) {
            return null;
        }

        return 'LIMIT ' . $processor->renderParameter($this->limit);
    }

    public function isEmpty(): bool
    {
        return $this->limit === null;
    }

    public function set(string|int|null $limit): void
    {
        $this->limit = $limit === null
            ? null
            : new Parameter($limit, preferredName: 'limit', typeHint: ParameterContainer::TYPE_INTEGER);
    }

    public function get(): string|int|null
    {
        return $this->limit?->getValue();
    }
}
