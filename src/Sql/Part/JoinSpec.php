<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\Expression;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function current;
use function get_debug_type;
use function is_array;
use function is_string;
use function key;
use function ctype_alpha;
use function ctype_alnum;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Normalized join specification.
 * All type discrimination (array alias, table type, expression ON)
 * happens at construction time.
 */
final readonly class JoinSpec
{
    public string|TableIdentifier|Select|ExpressionInterface $table;
    public JoinTableType $tableType;
    public ?string $alias;
    public PredicateInterface|string $on;
    public bool $isExpressionOn;
    public string $type;
    public array $columns;

    /** @var ColumnRef[] Pre-normalized column references */
    public array $columnRefs;

    /** @var ArgumentInterface[]|null Pre-tokenized ON clause (null for expression ON) */
    public ?array $onTokens;

    /** @param array{name: array|string|TableIdentifier, on: PredicateInterface|string, columns: array, type: string} $join */
    public function __construct(array $join)
    {
        $name = $join['name'];

        if (is_array($name)) {
            $this->alias = key($name);
            $table       = current($name);
        } else {
            $this->alias = null;
            $table       = $name;
        }

        $this->table     = $table;
        $this->tableType = match (true) {
            $table instanceof Expression       => JoinTableType::Expression,
            $table instanceof TableIdentifier  => JoinTableType::TableIdentifier,
            $table instanceof Select           => JoinTableType::Select,
            is_string($table)                  => JoinTableType::Identifier,
            default => throw new Exception\InvalidArgumentException(sprintf(
                'Join name expected to be Expression|TableIdentifier|Select|string, "%s" given',
                get_debug_type($table)
            )),
        };

        $on                   = $join['on'];
        $this->on             = $on;
        $this->isExpressionOn = $on instanceof ExpressionInterface;
        $this->type           = $join['type'];
        $this->columns        = $join['columns'];

        $refs = [];
        foreach ($join['columns'] as $key => $column) {
            $refs[] = new ColumnRef($key, $column);
        }
        $this->columnRefs = $refs;

        $this->onTokens = is_string($on) ? self::tokenizeOn($on) : null;
    }

    /** @return ArgumentInterface[] */
    private static function tokenizeOn(string $on): array
    {
        $tokens       = [];
        $len          = strlen($on);
        $pos          = 0;
        $literalStart = 0;

        while ($pos < $len) {
            $ch = $on[$pos];

            if ($ch === '_' || ctype_alpha($ch)) {
                $wordStart = $pos++;
                while ($pos < $len && ($on[$pos] === '_' || $on[$pos] === '.' || ctype_alnum($on[$pos]))) {
                    $pos++;
                }

                $word  = substr($on, $wordStart, $pos - $wordStart);
                $upper = strtoupper($word);

                if ($upper === 'AND' || $upper === 'OR' || $upper === 'AS' || $upper === 'BETWEEN'
                    || ($pos < $len && $on[$pos] === '(')
                ) {
                    continue;
                }

                if ($wordStart > $literalStart) {
                    $tokens[] = new Literal(substr($on, $literalStart, $wordStart - $literalStart));
                }
                $tokens[] = new Identifier($word);
                $literalStart = $pos;
            } else {
                $pos++;
            }
        }

        if ($literalStart < $len) {
            $tokens[] = new Literal(substr($on, $literalStart));
        }

        return $tokens ?: [new Literal($on)];
    }
}
