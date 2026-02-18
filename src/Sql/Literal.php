<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Sql\Part\SqlProcessor;

class Literal implements ExpressionInterface
{
    public function __construct(protected string $literal = '')
    {
        $this->literal = $literal;
    }

    public function setLiteral(string $literal): static
    {
        $this->literal = $literal;
        return $this;
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        return $this->literal;
    }
}
