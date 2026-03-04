<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Sql92 as DefaultAdapterPlatform;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Platform\Sql92Renderer;

abstract class AbstractSql implements SqlInterface
{
    private ?AbstractSqlRenderer $renderer = null;

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        $this->renderer ??= new Sql92Renderer();

        return $this->buildSqlString(
            $this->renderer->init($adapterPlatform ?? new DefaultAdapterPlatform())
        );
    }

    public function buildSqlString(AbstractSqlRenderer $renderer): string
    {
        return '';
    }
}
