<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Closure;
use Countable;
use Override;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Predicate\Expression as PredicateExpression;
use ReturnTypeWillChange;

use function count;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function str_contains;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class PredicateSet implements PredicateInterface, Countable
{
    final public const OP_AND = 'AND';

    final public const OP_OR = 'OR';

    /** @deprecated Use OP_AND instead */
    final public const COMBINED_BY_AND = self::OP_AND;

    /** @deprecated Use OP_OR instead */
    final public const COMBINED_BY_OR = self::OP_OR;

    protected string $defaultCombination = self::OP_AND;

    protected array $predicates = [];

    /**
     * Constructor
     */
    public function __construct(?array $predicates = null, string $defaultCombination = self::OP_AND)
    {
        $this->defaultCombination = $defaultCombination;

        if ($predicates !== null) {
            foreach ($predicates as $predicate) {
                $this->addPredicate($predicate);
            }
        }
    }

    /**
     * Add predicate to set
     */
    public function addPredicate(PredicateInterface $predicate, ?string $combination = null): static
    {
        $combination ??= $this->defaultCombination;

        match ($combination) {
            self::OP_AND => $this->andPredicate($predicate),
            self::OP_OR => $this->orPredicate($predicate),
            default => throw new Exception\InvalidArgumentException(
                "Invalid combination: expected 'AND' or 'OR'"
            ),
        };

        return $this;
    }

    /**
     * Add predicates to set
     *
     * @throws Exception\InvalidArgumentException
     */
    public function addPredicates(
        PredicateInterface|Closure|string|array $predicates,
        string $combination = self::OP_AND
    ): static {
        if (is_array($predicates)) {
            foreach ($predicates as $pkey => $pvalue) {
                if (is_string($pkey)) {
                    if (str_contains($pkey, '?')) {
                        $predicate = new PredicateExpression($pkey, $pvalue);
                    } elseif (is_scalar($pvalue)) {
                        $predicate = new Operator($pkey, Operator::OP_EQ, $pvalue);
                    } elseif ($pvalue === null) {
                        $predicate = new IsNull($pkey);
                    } elseif (is_array($pvalue)) {
                        $predicate = new In($pkey, $pvalue);
                    } elseif ($pvalue instanceof PredicateInterface) {
                        throw new Exception\InvalidArgumentException(
                            'Using Predicate must not use string keys'
                        );
                    } else {
                        $predicate = new Operator($pkey, Operator::OP_EQ, $pvalue);
                    }
                } elseif ($pvalue instanceof PredicateInterface) {
                    $predicate = $pvalue;
                } elseif ($pvalue instanceof Expression) {
                    $predicate = new PredicateExpression(
                        $pvalue->getExpression(),
                        $pvalue->getParameters()
                    );
                } else {
                    $predicate = str_contains($pvalue, Expression::PLACEHOLDER)
                        ? new Expression($pvalue) : new Literal($pvalue);
                }
                $this->predicates[] = [$combination, $predicate];
            }

            return $this;
        }

        if ($predicates instanceof PredicateInterface) {
            $this->addPredicate($predicates, $combination);

            return $this;
        }

        if ($predicates instanceof Closure) {
            $predicates($this);

            return $this;
        }

        $predicate = str_contains($predicates, Expression::PLACEHOLDER)
            ? new PredicateExpression($predicates) : new Literal($predicates);
        $this->addPredicate($predicate, $combination);

        return $this;
    }

    /**
     * Return the predicates
     */
    public function getPredicates(): array
    {
        return $this->predicates;
    }

    /**
     * Add predicate using OR operator
     */
    public function orPredicate(PredicateInterface $predicate): static
    {
        $this->predicates[] = [self::OP_OR, $predicate];

        return $this;
    }

    /**
     * Add predicate using AND operator
     */
    public function andPredicate(PredicateInterface $predicate): static
    {
        $this->predicates[] = [self::OP_AND, $predicate];

        return $this;
    }

    /**
     * Get count of attached predicates
     */
    #[Override]
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->predicates);
    }

    #[Override]
    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $predicateCount = count($this->predicates);

        if ($predicateCount === 0) {
            return '';
        }

        if ($predicateCount === 1) {
            [, $predicate] = $this->predicates[0];
            $sql           = $predicate->toSql($renderer, $paramPrefix, $paramIndex);

            return $predicate instanceof self ? "({$sql})" : $sql;
        }

        if ($predicateCount === 2) {
            [, $p1]     = $this->predicates[0];
            [$op2, $p2] = $this->predicates[1];
            $sql1       = $p1->toSql($renderer, $paramPrefix, $paramIndex);
            $sql2       = $p2->toSql($renderer, $paramPrefix, $paramIndex);
            if ($p1 instanceof self) {
                $sql1 = "({$sql1})";
            }
            if ($p2 instanceof self) {
                $sql2 = "({$sql2})";
            }

            return "{$sql1} {$op2} {$sql2}";
        }

        $parts = [];
        $first = true;

        foreach ($this->predicates as [$operator, $predicate]) {
            $sql = $predicate->toSql($renderer, $paramPrefix, $paramIndex);

            if ($predicate instanceof self) {
                $sql = "({$sql})";
            }

            $parts[] = $first ? $sql : "{$operator} {$sql}";
            $first   = false;
        }

        return implode(' ', $parts);
    }
}
