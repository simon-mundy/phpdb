<?php

declare(strict_types=1);

namespace PhpDb\Sql\Ddl;

use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\AbstractSql;
use PhpDb\Sql\TableIdentifier;

use function array_key_exists;

class DropTable extends AbstractSql
{
    final public const IF_EXISTS = 'ifExists';

    final public const TABLE = 'table';

    protected bool $ifExists = false;

    protected array $specifications = [
        self::TABLE => 'DROP TABLE %1$s%2$s',
    ];

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

    /**
     * @return array|string
     */
    public function getRawState(?string $key = null): array|string
    {
        $rawState = [
            self::TABLE     => $this->table,
            self::IF_EXISTS => $this->ifExists,
        ];

        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }

    /** @return string[] */
    protected function processTable(?PlatformInterface $adapterPlatform = null): array
    {
        return [
            $this->ifExists ? 'IF EXISTS ' : '',
            $this->resolveTable($this->table, $adapterPlatform),
        ];
    }
}
