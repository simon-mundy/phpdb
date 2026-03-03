<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

final readonly class Value implements ArgumentInterface
{
    /**
     * @param null|string|int|float|bool $value Scalar value, or null
     */
    public function __construct(
        private null|string|int|float|bool $value
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Value;
    }

    public function getValue(): null|string|int|float|bool
    {
        return $this->value;
    }

    public function render(AbstractSqlRenderer $renderer, string $paramPrefix, int &$paramIndex): string
    {
        return $renderer->parameterContainer !== null
            ? $renderer->bindValue($this->value, $paramPrefix, $paramIndex)
            : $renderer->platform->quoteValue((string) $this->value);
    }
}
