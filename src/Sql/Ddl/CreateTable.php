<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function implode;
use function is_bool;
use function is_int;
use function strtoupper;

class CreateTable extends AbstractDdl
{
    final public const COLUMNS = 'columns';

    final public const CONSTRAINTS = 'constraints';

    final public const TABLE = 'table';

    final public const TABLE_OPTIONS = 'tableOptions';

    protected array $columns = [];

    protected array $constraints = [];

    protected bool $ifNotExists = false;

    protected bool $isTemporary = false;

    protected array $options = [];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '', bool $isTemporary = false)
    {
        $this->table = $table;
        $this->setTemporary($isTemporary);
    }

    public function ifNotExists(bool $ifNotExists = true): static
    {
        $this->ifNotExists = $ifNotExists;
        return $this;
    }

    public function getIfNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function setTemporary(string|int|bool $temporary): static
    {
        $this->isTemporary = (bool) $temporary;
        return $this;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }

    public function setTable(string $name): static
    {
        $this->table = $name;
        return $this;
    }

    public function addColumn(Column\ColumnInterface $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    public function addConstraint(Constraint\ConstraintInterface $constraint): static
    {
        $this->constraints[] = $constraint;
        return $this;
    }

    public function setOption(string $name, Literal|bool|int|string $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return ((Column\ColumnInterface|string)[]|Column\ColumnInterface|string)[]|string
     * @psalm-return array<Column\ColumnInterface|array<Column\ColumnInterface|string>|string>|string
     */
    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::COLUMNS       => $this->columns,
            self::CONSTRAINTS   => $this->constraints,
            self::TABLE         => $this->table,
            self::TABLE_OPTIONS => $this->options,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    #[Override]
    public function buildSqlString(AbstractSqlRenderer $renderer): string
    {
        $sql = 'CREATE '
            . ($this->isTemporary ? 'TEMPORARY ' : '')
            . 'TABLE '
            . ($this->ifNotExists ? 'IF NOT EXISTS ' : '')
            . $renderer->renderTableSource($this->table) . ' (';

        if ($this->columns) {
            $columnSqls = [];
            foreach ($this->columns as $column) {
                $columnSqls[] = $renderer->render($column);
            }
            $sql .= " \n    " . implode(",\n    ", $columnSqls);
        }

        if ($this->columns && $this->constraints) {
            $sql .= ' ,';
        }

        if ($this->constraints) {
            $constraintSqls = [];
            foreach ($this->constraints as $constraint) {
                $constraintSqls[] = $renderer->render($constraint);
            }
            $sql .= " \n    " . implode(",\n    ", $constraintSqls);
        }

        $sql .= " \n)";

        if ($this->options) {
            $sql .= ' ' . $this->renderTableOptions($renderer->platform);
        }

        return $sql;
    }

    private function renderTableOptions(PlatformInterface $platform): string
    {
        $parts = [];
        foreach ($this->options as $key => $value) {
            $key = strtoupper($key);
            if ($value instanceof Literal) {
                $value = $value->getLiteral();
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_int($value)) {
                $value = (string) $value;
            } else {
                $value = $platform->quoteTrustedValue($value);
            }
            $parts[] = $key . ' = ' . $value;
        }

        return implode(' ', $parts);
    }
}
