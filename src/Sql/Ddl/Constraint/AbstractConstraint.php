<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Part\SqlProcessor;

use function implode;
use function str_replace;

abstract class AbstractConstraint implements ConstraintInterface
{
    protected string $columnSpecification = '(%s)';

    protected string $namedSpecification = 'CONSTRAINT %s';

    protected string $specification = '';

    protected string $name = '';

    protected array $columns = [];

    public function __construct(null|array|string $columns = null, ?string $name = null)
    {
        if ($columns !== null) {
            $this->setColumns($columns);
        }

        if ($name !== null) {
            $this->setName($name);
        }
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setColumns(string|array $columns): static
    {
        $this->columns = (array) $columns;

        return $this;
    }

    public function addColumn(string $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    #[Override] public function getColumns(): array
    {
        return $this->columns;
    }

    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $parts = [];

        if ($this->name !== '') {
            $parts[] = 'CONSTRAINT ' . $processor->renderArgument(
                new Identifier($this->name),
                $paramPrefix,
                $paramIndex,
            );
        }

        if ($this->specification !== '') {
            $parts[] = $this->specification;
        }

        if ($this->columns !== []) {
            $quotedColumns = [];
            foreach ($this->columns as $column) {
                $quotedColumns[] = $processor->renderArgument(
                    new Identifier($column),
                    $paramPrefix,
                    $paramIndex,
                );
            }
            $parts[] = str_replace('%s', implode(', ', $quotedColumns), $this->columnSpecification);
        }

        return implode(' ', $parts);
    }
}
