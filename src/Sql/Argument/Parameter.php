<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

final readonly class Parameter implements ArgumentInterface
{
    /**
     * @param null|string|int|float|bool $value      The scalar value to bind
     * @param ?string                    $preferredName  Preferred parameter name (e.g. 'limit', column name)
     * @param ?string                    $typeHint       Type hint for ParameterContainer (e.g. TYPE_INTEGER)
     */
    public function __construct(
        public null|string|int|float|bool $value,
        public ?string $preferredName = null,
        public ?string $typeHint = null,
    ) {
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Parameter;
    }

    public function getValue(): null|string|int|float|bool
    {
        return $this->value;
    }

    public function getPreferredName(): ?string
    {
        return $this->preferredName;
    }

    public function getTypeHint(): ?string
    {
        return $this->typeHint;
    }
}
