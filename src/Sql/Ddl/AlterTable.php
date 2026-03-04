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
use function rtrim;
use function strtoupper;

class AlterTable extends AbstractDdl
{
    final public const ADD_COLUMNS = 'addColumns';

    final public const ADD_CONSTRAINTS = 'addConstraints';

    final public const CHANGE_COLUMNS = 'changeColumns';

    final public const DROP_COLUMNS = 'dropColumns';

    final public const DROP_CONSTRAINTS = 'dropConstraints';

    final public const DROP_INDEXES = 'dropIndexes';

    final public const TABLE = 'table';

    final public const TABLE_OPTIONS = 'tableOptions';

    protected array $addColumns = [];

    protected array $addConstraints = [];

    protected array $changeColumns = [];

    protected array $dropColumns = [];

    protected array $dropConstraints = [];

    protected array $dropIndexes = [];

    protected array $options = [];

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        if ($table) {
            $this->setTable($table);
        }
    }

    public function setTable(string|TableIdentifier $name): static
    {
        $this->table = $name;

        return $this;
    }

    public function addColumn(Column\ColumnInterface $column): static
    {
        $this->addColumns[] = $column;

        return $this;
    }

    public function changeColumn(string $name, Column\ColumnInterface $column): static
    {
        $this->changeColumns[$name] = $column;

        return $this;
    }

    public function dropColumn(string $name): static
    {
        $this->dropColumns[] = $name;

        return $this;
    }

    public function dropConstraint(string $name): static
    {
        $this->dropConstraints[] = $name;

        return $this;
    }

    public function addConstraint(Constraint\ConstraintInterface $constraint): static
    {
        $this->addConstraints[] = $constraint;

        return $this;
    }

    /**
     * @return static Provides a fluent interface
     */
    public function dropIndex(string $name): static
    {
        $this->dropIndexes[] = $name;

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

    #[Override]
    public function buildSqlString(AbstractSqlRenderer $renderer): string
    {
        $sql = "ALTER TABLE " . $renderer->renderTableSource($this->table) . "\n";

        $clauses = [];

        foreach ($this->addColumns as $column) {
            $clauses[] = 'ADD COLUMN ' . $renderer->render($column);
        }

        foreach ($this->changeColumns as $name => $column) {
            $clauses[] = 'CHANGE COLUMN ' . $renderer->platform->quoteIdentifier($name)
                . ' ' . $renderer->render($column);
        }

        foreach ($this->dropColumns as $column) {
            $clauses[] = 'DROP COLUMN ' . $renderer->platform->quoteIdentifier($column);
        }

        foreach ($this->addConstraints as $constraint) {
            $clauses[] = 'ADD ' . $renderer->render($constraint);
        }

        foreach ($this->dropConstraints as $constraint) {
            $clauses[] = 'DROP CONSTRAINT ' . $renderer->platform->quoteIdentifier($constraint);
        }

        foreach ($this->dropIndexes as $index) {
            $clauses[] = 'DROP INDEX ' . $renderer->platform->quoteIdentifier($index);
        }

        if ($this->options) {
            $clauses[] = $this->renderTableOptions($renderer->platform);
        }

        $sql .= ' ' . implode(",\n ", $clauses);

        return rtrim($sql, "\n ,");
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

        return implode(', ', $parts);
    }

    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::TABLE            => $this->table,
            self::ADD_COLUMNS      => $this->addColumns,
            self::DROP_COLUMNS     => $this->dropColumns,
            self::CHANGE_COLUMNS   => $this->changeColumns,
            self::ADD_CONSTRAINTS  => $this->addConstraints,
            self::DROP_CONSTRAINTS => $this->dropConstraints,
            self::DROP_INDEXES     => $this->dropIndexes,
            self::TABLE_OPTIONS    => $this->options,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }
}
