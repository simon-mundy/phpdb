<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use Override;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\TableIdentifier;

class DropTable extends AbstractDdl
{
    final public const TABLE = 'table';

    protected bool $ifExists = false;

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        $this->table = $table;
    }

    public function ifExists(bool $ifExists = true): static
    {
        $this->ifExists = $ifExists;
        return $this;
    }

    public function getIfExists(): bool
    {
        return $this->ifExists;
    }

    #[Override]
    public function buildSqlString(AbstractSqlRenderer $renderer): string
    {
        return 'DROP TABLE '
            . ($this->ifExists ? 'IF EXISTS ' : '')
            . $renderer->renderTableSource($this->table);
    }
}
