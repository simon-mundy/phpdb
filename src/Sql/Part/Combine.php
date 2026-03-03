<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;

class Combine extends AbstractPart
{
    private array $combine = [];

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->combine === []) {
            return null;
        }

        $type = $this->combine['modifier']
            ? "{$this->combine['type']} {$this->combine['modifier']}"
            : $this->combine['type'];

        return $type . ' ( '
            . $renderer->processSubSelect($this->combine['select'])
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
