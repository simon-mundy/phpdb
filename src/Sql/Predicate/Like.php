<?php

declare(strict_types=1);

namespace PhpDb\Sql\Predicate;

use Override;
use PhpDb\Sql\AbstractExpression;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\Exception\InvalidArgumentException;
use PhpDb\Sql\Part\SqlProcessor;

use function vsprintf;

class Like extends AbstractExpression implements PredicateInterface
{
    protected string $operator               = 'LIKE';
    protected ?ArgumentInterface $identifier = null;
    protected ?ArgumentInterface $like       = null;

    /**
     * Constructor
     */
    public function __construct(
        null|string|ArgumentInterface $identifier = null,
        null|bool|float|int|string|ArgumentInterface $like = null
    ) {
        if ($identifier !== null) {
            $this->setIdentifier($identifier);
        }

        if ($like !== null) {
            $this->setLike($like);
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

    public function getIdentifier(): ?ArgumentInterface
    {
        return $this->identifier;
    }

    /**
     * Set like pattern for comparison
     */
    public function setLike(bool|float|int|null|string|ArgumentInterface $like): static
    {
        $this->like = $like instanceof ArgumentInterface
            ? $like
            : new Value($like);

        return $this;
    }

    public function getLike(): ?ArgumentInterface
    {
        return $this->like;
    }

    #[Override]
    public function toSql(SqlProcessor $processor, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if (! $this->identifier instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Identifier must be specified');
        }

        if (! $this->like instanceof ArgumentInterface) {
            throw new InvalidArgumentException('Like expression must be specified');
        }

        $id   = $processor->renderArgument($this->identifier, $paramPrefix, $paramIndex);
        $like = $processor->renderArgument($this->like, $paramPrefix, $paramIndex);

        if ($this->specification !== null) {
            return vsprintf($this->specification, [$id, $like]);
        }

        return "{$id} {$this->operator} {$like}";
    }
}
