<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Join;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function gettype;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;
use function strtoupper;

/**
 * Wraps a Join model and renders all JOIN clauses.
 * Used by Select and Update.
 */
class Joins extends AbstractPart
{
    private ?Join $joins = null;

    public function toSql(SqlPartProcessor $processor): ?string
    {
        if ($this->joins === null || $this->joins->count() === 0) {
            return null;
        }

        $joinSqlParts = [];

        foreach ($this->joins->getJoins() as $j => $join) {
            $joinAs        = null;
            $joinNameValue = $join['name'];

            if (is_array($joinNameValue)) {
                $joinName = current($joinNameValue);
                $joinAs   = $processor->platform->quoteIdentifier(key($joinNameValue));
            } else {
                $joinName = $joinNameValue;
            }

            if ($joinName instanceof Expression) {
                $joinName = $joinName->getExpression();
            } elseif ($joinName instanceof TableIdentifier) {
                $joinName = $joinName->getTableAndSchema();
                $joinName = ($joinName[1]
                        ? $processor->platform->quoteIdentifier($joinName[1])
                            . $processor->platform->getIdentifierSeparator()
                        : '') . $processor->platform->quoteIdentifier($joinName[0]);
            } elseif ($joinName instanceof Select) {
                $joinName = '(' . $processor->processSubSelect($joinName) . ')';
            } elseif (is_string($joinName) || (is_object($joinName) && is_callable([$joinName, '__toString']))) {
                $joinName = $processor->platform->quoteIdentifier($joinName);
            } else {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Join name expected to be Expression|TableIdentifier|Select|string, "%s" given',
                    gettype($joinName)
                ));
            }

            $renderedTable = $processor->renderTable($joinName, $joinAs);
            $type          = strtoupper($join['type']);

            if ($join['on'] instanceof ExpressionInterface) {
                $onClause = $processor->processExpression(
                    $join['on'],
                    'join' . ($j + 1) . 'part'
                );
            } else {
                $onClause = $processor->platform->quoteIdentifierInFragment(
                    $join['on'],
                    ['=', 'AND', 'OR', '(', ')', 'BETWEEN', '<', '>']
                );
            }

            $joinSqlParts[] = "{$type} JOIN {$renderedTable} ON {$onClause}";
        }

        return implode(' ', $joinSqlParts);
    }

    public function isEmpty(): bool
    {
        return $this->joins === null || $this->joins->count() === 0;
    }

    /**
     * Add a join specification.
     */
    public function join(
        array|string|TableIdentifier $name,
        PredicateInterface|string $on,
        array|string $columns = Select::SQL_STAR,
        string $type = Join::JOIN_INNER
    ): void {
        $this->getModel()->join($name, $on, $columns, $type);
    }

    /**
     * Get the underlying Join model, creating it lazily.
     */
    public function getModel(): Join
    {
        return $this->joins ??= new Join();
    }

    /**
     * Replace the underlying Join model.
     */
    public function setModel(?Join $joins): void
    {
        $this->joins = $joins;
    }

    public function __clone()
    {
        if ($this->joins !== null) {
            $this->joins = clone $this->joins;
        }
    }
}
