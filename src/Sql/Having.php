<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Clause\ClauseInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Platform\Sql92Renderer;

class Having extends Predicate\Predicate implements ClauseInterface
{
    #[Override]
    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        if ($this->count() === 0) {
            return null;
        }

        if ($paramPrefix !== '') {
            return parent::toSql($renderer, $paramPrefix, $paramIndex);
        }

        if ($renderer->parameterContainer !== null) {
            return 'HAVING ' . $renderer->render($this, 'having');
        }

        $pi = 0;
        return 'HAVING ' . parent::toSql($renderer, '', $pi);
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function __toString(): string
    {
        return $this->toSql((new Sql92Renderer())->init(new Sql92())) ?? '';
    }
}
