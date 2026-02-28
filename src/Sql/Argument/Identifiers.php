<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Part\SqlProcessor;

use function implode;

final readonly class Identifiers implements ArgumentInterface
{
    /** @var Identifier[] */
    public array $identifiers;

    /**
     * @param list<string> $identifiers
     */
    public function __construct(array $identifiers)
    {
        $items = [];
        foreach ($identifiers as $id) {
            $items[] = new Identifier($id);
        }
        $this->identifiers = $items;
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifiers;
    }

    /**
     * @return list<string>
     */
    public function getValue(): array
    {
        $result = [];
        foreach ($this->identifiers as $id) {
            $result[] = $id->getValue();
        }
        return $result;
    }

    public function render(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        $quoted = [];
        foreach ($this->identifiers as $identifier) {
            $quoted[] = $identifier->render($processor, $paramPrefix, $paramIndex);
        }
        return implode(', ', $quoted);
    }
}
