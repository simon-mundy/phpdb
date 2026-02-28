<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Part\SqlProcessor;

use function implode;

class ForeignKey extends AbstractConstraint
{
    protected string $onDeleteRule = 'NO ACTION';

    protected string $onUpdateRule = 'NO ACTION';

    protected string $referenceTable = '';

    protected string $columnSpecification = 'FOREIGN KEY (%s)';

    /** @var string[] */
    protected array $referenceColumn = [];

    /**
     * @param string[]|string|null $referenceColumn
     */
    public function __construct(
        string $name,
        string|array $columns,
        string $referenceTable,
        array|string|null $referenceColumn,
        null|string $onDeleteRule = null,
        null|string $onUpdateRule = null
    ) {
        parent::__construct($columns, $name);

        $this->setReferenceTable($referenceTable);

        if ($referenceColumn !== null) {
            $this->setReferenceColumn($referenceColumn);
        }

        if ($onDeleteRule !== null) {
            $this->setOnDeleteRule($onDeleteRule);
        }

        if ($onUpdateRule !== null) {
            $this->setOnUpdateRule($onUpdateRule);
        }
    }

    public function getReferenceTable(): string
    {
        return $this->referenceTable;
    }

    public function setReferenceTable(string $referenceTable): static
    {
        $this->referenceTable = $referenceTable;

        return $this;
    }

    public function getReferenceColumn(): array
    {
        return $this->referenceColumn;
    }

    /**
     * @param string[]|string $referenceColumn
     */
    public function setReferenceColumn(array|string $referenceColumn): static
    {
        $this->referenceColumn = (array) $referenceColumn;

        return $this;
    }

    public function getOnDeleteRule(): string
    {
        return $this->onDeleteRule;
    }

    public function setOnDeleteRule(string $onDeleteRule): static
    {
        $this->onDeleteRule = $onDeleteRule;

        return $this;
    }

    public function getOnUpdateRule(): string
    {
        return $this->onUpdateRule;
    }

    public function setOnUpdateRule(string $onUpdateRule): static
    {
        $this->onUpdateRule = $onUpdateRule;

        return $this;
    }

    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $sql = parent::toSql($processor, $paramPrefix, $paramIndex);

        $sql .= ' REFERENCES ' . $processor->renderArgument(
            new Identifier($this->referenceTable),
            $paramPrefix,
            $paramIndex,
        );

        if ($this->referenceColumn !== []) {
            $quotedColumns = [];
            foreach ($this->referenceColumn as $column) {
                $quotedColumns[] = $processor->renderArgument(
                    new Identifier($column),
                    $paramPrefix,
                    $paramIndex,
                );
            }
            $sql .= ' (' . implode(', ', $quotedColumns) . ')';
        }

        $sql .= ' ON DELETE ' . $this->onDeleteRule
            . ' ON UPDATE ' . $this->onUpdateRule;

        return $sql;
    }
}
