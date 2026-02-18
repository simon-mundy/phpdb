<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Sql\Part\SqlProcessor;

abstract class AbstractExpression implements ExpressionInterface
{
    protected ?string $specification = null;

    /**
     * Set specification string to override the default
     */
    public function setSpecification(string $specification): static
    {
        $this->specification = $specification;

        return $this;
    }

    /**
     * Get specification override, or null if not set
     */
    public function getSpecification(): ?string
    {
        return $this->specification;
    }

    /**
     * Default renderSql() — delegates to processExpression() for backward compatibility.
     * Subclasses override this with direct rendering for performance.
     */
    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        return $processor->processExpression($this, $paramPrefix);
    }
}
