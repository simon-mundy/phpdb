<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Index;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Part\SqlProcessor;

use function implode;

class Index extends AbstractIndex
{
    protected string $specification = 'INDEX %s(...)';

    protected array $lengths;

    protected ?string $type = null;

    public function __construct(null|array|string $columns, ?string $name = null, array $lengths = [])
    {
        parent::__construct($columns, $name);

        $this->lengths = $lengths;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        $quotedName = $processor->renderArgument(
            new Identifier($this->name),
            $paramPrefix,
            $paramIndex,
        );

        $columnParts = [];
        foreach ($this->columns as $i => $column) {
            $part = $processor->renderArgument(
                new Identifier($column),
                $paramPrefix,
                $paramIndex,
            );

            if (isset($this->lengths[$i])) {
                $part .= '(' . $this->lengths[$i] . ')';
            }

            $columnParts[] = $part;
        }

        $sql = 'INDEX ' . $quotedName . '(' . implode(', ', $columnParts) . ')';

        if ($this->type !== null) {
            $sql .= ' USING ' . $this->type;
        }

        return $sql;
    }
}
