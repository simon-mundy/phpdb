<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Platform\AbstractPlatform as SqlPlatform;
use PhpDb\Sql\TableIdentifier;

class DropTable extends AbstractDdl
{
    final public const TABLE = 'table';

    protected string|TableIdentifier $table = '';

    public function __construct(string|TableIdentifier $table = '')
    {
        $this->table = $table;
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?SqlPlatform $sqlPlatform = null,
    ): string {
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $sqlPlatform);

        return 'DROP TABLE ' . $processor->resolveTable($this->table);
    }
}
