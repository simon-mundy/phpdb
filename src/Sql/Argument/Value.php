<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Part\SqlProcessor;

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

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        return $processor->parameterContainer !== null
            ? $processor->bindValue($this->value, $paramPrefix, $paramIndex)
            : $processor->platform->quoteValue((string) $this->value);
    }
}
