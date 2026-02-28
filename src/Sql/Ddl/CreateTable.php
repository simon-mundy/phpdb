<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Platform\AbstractPlatform as SqlPlatform;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;
use function implode;

class CreateTable extends AbstractDdl
{
    final public const COLUMNS = 'columns';

    final public const CONSTRAINTS = 'constraints';

    final public const TABLE = 'table';

    protected array $columns = [];

    protected array $constraints = [];

    protected bool $isTemporary = false;

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '', bool $isTemporary = false)
    {
        $this->table = $table;
        $this->setTemporary($isTemporary);
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

    /**
     * @return ((Column\ColumnInterface|string)[]|Column\ColumnInterface|string)[]|string
     * @psalm-return array<Column\ColumnInterface|array<Column\ColumnInterface|string>|string>|string
     */
    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::COLUMNS     => $this->columns,
            self::CONSTRAINTS => $this->constraints,
            self::TABLE       => $this->table,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?SqlPlatform $sqlPlatform = null,
    ): string {
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $sqlPlatform);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        // CREATE [TEMPORARY] TABLE "name" (
        $sql = 'CREATE '
            . ($this->isTemporary ? 'TEMPORARY ' : '')
            . 'TABLE ' . $processor->resolveTable($this->table) . ' (';

        // Columns
        if ($this->columns) {
            $columnSqls = [];
            foreach ($this->columns as $column) {
                $columnSqls[] = $processor->renderExpression($column);
            }
            $sql .= " \n    " . implode(",\n    ", $columnSqls);
        }

        // Separator between columns and constraints
        if ($this->columns && $this->constraints) {
            $sql .= ' ,';
        }

        // Constraints
        if ($this->constraints) {
            $constraintSqls = [];
            foreach ($this->constraints as $constraint) {
                $constraintSqls[] = $processor->renderExpression($constraint);
            }
            $sql .= " \n    " . implode(",\n    ", $constraintSqls);
        }

        $sql .= " \n)";

        return $sql;
    }
}
