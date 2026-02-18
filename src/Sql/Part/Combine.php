<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Select;

use function strtoupper;

/**
 * Holds and renders UNION/EXCEPT/INTERSECT clause for SELECT statements.
 */
class Combine extends AbstractPart
{
    private array $combine = [];

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->combine === []) {
            return null;
        }

        $type = $this->combine['modifier']
            ? "{$this->combine['type']} {$this->combine['modifier']}"
            : $this->combine['type'];

        return strtoupper($type) . ' ( '
            . $processor->processSubSelect($this->combine['select'])
            . ' )';
    }

    public function isEmpty(): bool
    {
        return $this->combine === [];
    }

    public function set(Select $select, string $type, string $modifier = ''): void
    {
        $this->combine = [
            'select'   => $select,
            'type'     => $type,
            'modifier' => $modifier,
        ];
    }

    public function get(): array
    {
        return $this->combine;
    }

    public function reset(): void
    {
        $this->combine = [];
    }
}
