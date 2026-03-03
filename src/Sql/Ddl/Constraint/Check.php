<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Constraint;

use Override;
use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\ExpressionInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

use function implode;

class Check extends AbstractConstraint
{
    protected string|ExpressionInterface $expression;

    protected string $specification = 'CHECK (%s)';

    /**
     * @param string|ExpressionInterface $expression
     */
    public function __construct($expression, ?string $name)
    {
        parent::__construct(null, $name);

        $this->expression = $expression;
    }

    #[Override]
    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $parts = [];

        if ($this->name !== '') {
            $parts[] = 'CONSTRAINT ' . $renderer->renderArgument(
                new Identifier($this->name),
                $paramPrefix,
                $paramIndex,
            );
        }

        if ($this->expression !== '') {
            $parts[] = 'CHECK (' . $this->expression . ')';
        }

        return implode(' ', $parts);
    }
}
