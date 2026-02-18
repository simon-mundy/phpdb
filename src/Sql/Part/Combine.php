<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Select;

/**
 * Holds and renders UNION/EXCEPT/INTERSECT clause for SELECT statements.
 */
class Combine extends AbstractPart
{
    private array $combine = [];

    public function toSql(SqlProcessor $processor): ?string
    {
        if ($this->combine === []) {
            return null;
        }

        $type = $this->combine['modifier']
            ? "{$this->combine['type']} {$this->combine['modifier']}"
            : $this->combine['type'];

        return $type . ' ( '
            . $processor->processSubSelect($this->combine['select'])
            . ' )';
    }

    public function isEmpty(): bool
    {
        return $this->combine === [];
    }

    public function set(Select $select, string $type, string $modifier = ''): static
    {
        $this->combine = [
            'select'   => $select,
            'type'     => $type,
            'modifier' => $modifier,
        ];
        return $this;
    }

    public function get(): array
    {
        return $this->combine;
    }

    public function reset(): static
    {
        $this->combine = [];
        return $this;
    }
}
