<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

/**
 * Represents a value that should be bound as a driver parameter.
 * Carries optional metadata for parameter naming and type hinting.
 *
 * Unlike Value (which is rendered via processExpression with auto-named params),
 * Parameter is used for direct bind scenarios (SET, INSERT VALUES, LIMIT, OFFSET)
 * where the parameter name and type hint are predetermined.
 */
final readonly class Parameter implements ArgumentInterface
{
    /**
     * @param null|string|int|float|bool $value      The scalar value to bind
     * @param ?string                    $preferredName  Preferred parameter name (e.g. 'limit', column name)
     * @param ?string                    $typeHint       Type hint for ParameterContainer (e.g. TYPE_INTEGER)
     */
    public function __construct(
        private null|string|int|float|bool $value,
        private ?string $preferredName = null,
        private ?string $typeHint = null,
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

    public function getSpecification(): string
    {
        return '%s';
    }
}
