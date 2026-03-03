<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

class Limit extends AbstractPart
{
    private ?Parameter $limit = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->limit === null) {
            return null;
        }

        return 'LIMIT ' . $renderer->bindParameter($this->limit);
    }

    public function isEmpty(): bool
    {
        return $this->limit === null;
    }

    public function set(string|int|null $limit): static
    {
        $this->limit = $limit === null
            ? null
            : new Parameter($limit, preferredName: 'limit', typeHint: ParameterContainer::TYPE_INTEGER);
        return $this;
    }

    public function get(): string|int|null
    {
        return $this->limit?->getValue();
    }
}
