<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
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
        ?ParameterContainer $parameterContainer = null
    ): string {
        $this->localizeVariables();

        $decorator = $this instanceof PlatformDecoratorInterface ? $this : null;
        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $decorator);

        return 'DROP TABLE ' . $processor->resolveTable($this->table);
    }
}
