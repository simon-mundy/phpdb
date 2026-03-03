<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

class Integer extends Column
{
    #[Override]
    public function toSql(AbstractSqlRenderer $renderer, string $paramPrefix = '', int &$paramIndex = 0): ?string
    {
        $sql     = parent::toSql($renderer, $paramPrefix, $paramIndex);
        $options = $this->getOptions();

        if (isset($options['length'])) {
            $sql .= ' (' . $options['length'] . ')';
        }

        return $sql;
    }
}
