<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

class Offset extends AbstractPart
{
    private ?Value $offset = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->offset === null) {
            return null;
        }

        $pi = 0;

        return 'OFFSET ' . $renderer->renderArgument(
            $this->offset,
            $renderer->getParamPrefix() . 'offset',
            $pi,
        );
    }

    public function isEmpty(): bool
    {
        return $this->offset === null;
    }

    public function set(string|int $offset): static
    {
        $this->offset = new Value($offset, ParameterContainer::TYPE_INTEGER);
        return $this;
    }

    public function get(): string|int|null
    {
        return $this->offset?->value;
    }
}
