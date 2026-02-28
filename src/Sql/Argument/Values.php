<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Part\SqlProcessor;

use function array_values;
use function implode;

final readonly class Values implements ArgumentInterface
{
    /** @var list<null|string|int|float|bool> */
    private array $values;

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

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        $rendered = [];

        if ($processor->parameterContainer !== null) {
            foreach ($this->values as $value) {
                $rendered[] = $processor->bindValue($value, $paramPrefix, $paramIndex);
            }
        } else {
            foreach ($this->values as $value) {
                $rendered[] = $processor->platform->quoteValue((string) $value);
            }
        }

        return '(' . implode(', ', $rendered) . ')';
    }
}
