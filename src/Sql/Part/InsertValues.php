<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Adapter\Driver\PdoDriverInterface;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Argument\Parameter;
use PhpDb\Sql\Argument\Select as SelectArgument;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Exception;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Select;

use function implode;

class InsertValues extends AbstractPart
{
    private string $keyword = 'INSERT INTO';
    private From $table;

    /** @var InsertColumnValue[] */
    private array $columnValues = [];

    private bool $hasSelect = false;

    public function __construct(From $table)
    {
        $this->table = $table;
    }

    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->hasSelect) {
            return null;
        }

        if ($this->columnValues === []) {
            throw new Exception\InvalidArgumentException('values or select should be present');
        }

        $columns     = [];
        $values      = [];
        $i           = 0;
        $isPdoDriver = $renderer->driver instanceof PdoDriverInterface;

        foreach ($this->columnValues as $cv) {
            $columns[] = AbstractSqlRenderer::QI_OPEN . $cv->column . AbstractSqlRenderer::QI_CLOSE;

            $values[] = match ($cv->value->getType()) {
                ArgumentType::Parameter => $renderer->bindParameter(
                    $cv->value,
                    $isPdoDriver ? 'c_' . $i++ : null
                ),
                ArgumentType::Select  => $renderer->render($cv->value->getValue()),
                ArgumentType::Literal => $cv->value->getValue(),
                default               => $renderer->platform->quoteValue((string) $cv->value->getValue()),
            };
        }

        $tableSql = $this->table->renderTable($renderer);

        return $this->keyword . ' ' . $tableSql
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $values) . ')';
    }

    public function isEmpty(): bool
    {
        return $this->hasSelect || $this->columnValues === [];
    }

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    /** @param array<string, mixed> $columns */
    public function setColumns(array $columns): void
    {
        $this->columnValues = [];
        foreach ($columns as $column => $value) {
            $this->columnValues[] = new InsertColumnValue($column, $this->normalizeValue($column, $value));
        }
    }

    public function getColumns(): array
    {
        $result = [];
        foreach ($this->columnValues as $cv) {
            $result[$cv->column] = $cv->value->getValue();
        }
        return $result;
    }

    public function setHasSelect(bool $hasSelect): void
    {
        $this->hasSelect = $hasSelect;
    }

    private function normalizeValue(string $column, mixed $value): ArgumentInterface
    {
        if ($value instanceof ArgumentInterface) {
            return $value;
        }

        if ($value instanceof Select) {
            return new SelectArgument($value);
        }

        if ($value instanceof ExpressionInterface) {
            return new SelectArgument($value);
        }

        if ($value === null) {
            return new Literal('NULL');
        }

        return new Parameter($value, preferredName: $column);
    }
}
