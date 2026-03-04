<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\ParameterContainer;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

class Limit extends AbstractPart
{
    private ?Value $limit = null;

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->limit === null) {
            return null;
        }

        $pi = 0;

        return 'LIMIT ' . $renderer->renderArgument(
            $this->limit,
            $renderer->getParamPrefix() . 'limit',
            $pi,
        );
    }

    public function isEmpty(): bool
    {
        return $this->limit === null;
    }

    public function set(string|int $limit): static
    {
        $this->limit = new Value($limit, ParameterContainer::TYPE_INTEGER);
        return $this;
    }

    public function get(): string|int|null
    {
        return $this->limit?->value;
    }
}
