<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;

use function array_values;

final readonly class Values implements ArgumentInterface
{
    /** @var list<null|string|int|float|bool> */
    public array $values;

    /**
     * @param list<null|string|int|float|bool> $values
     */
    public function __construct(array $values)
    {
        $this->values = array_values($values);
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Values;
    }

    /**
     * @return list<null|string|int|float|bool>
     */
    public function getValue(): array
    {
        return $this->values;
    }
}
