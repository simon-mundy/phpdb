<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl\Column;

use Override;
use PhpDb\Sql\Part\SqlProcessor;

class Integer extends Column
{
    #[Override]
    public function renderSql(SqlProcessor $processor, string $paramPrefix, int &$paramIndex): string
    {
        $sql     = parent::renderSql($processor, $paramPrefix, $paramIndex);
        $options = $this->getOptions();

        if (isset($options['length'])) {
            $sql .= ' (' . $options['length'] . ')';
        }

        return $sql;
    }
}
