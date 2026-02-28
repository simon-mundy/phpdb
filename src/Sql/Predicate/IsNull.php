<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Part\SqlProcessor;

use function vsprintf;

class IsNull extends AbstractExpression implements PredicateInterface
{
    protected string $operator = 'IS NULL';

    protected ?ArgumentInterface $identifier = null;

    /**
     * Constructor
     */
    public function __construct(null|string|ArgumentInterface $identifier = null)
    {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
        }
    }

    /**
     * Set identifier for comparison
     */
    public function setIdentifier(string|ArgumentInterface $identifier): static
    {
        $this->identifier = $identifier instanceof ArgumentInterface
            ? $identifier
            : new Identifier($identifier);

        return $this;
    }

    /**
     * Get identifier of comparison
     */
    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        $id = $processor->renderArgument($this->identifier, $paramPrefix, $paramIndex);

        if ($this->specification !== null) {
            return vsprintf($this->specification, [$id]);
        }

        return "{$id} {$this->operator}";
    }
}
