<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

class Offset extends AbstractPart
{
    private ?Parameter $offset = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->offset === null) {
            return null;
        }

        return 'OFFSET ' . $renderer->bindParameter($this->offset);
    }

    public function isEmpty(): bool
    {
        return $this->offset === null;
    }

    public function set(string|int|null $offset): static
    {
        $this->offset = $offset === null
            ? null
            : new Parameter($offset, preferredName: 'offset', typeHint: ParameterContainer::TYPE_INTEGER);
        return $this;
    }

    public function get(): string|int|null
    {
        return $this->offset?->getValue();
    }
}
