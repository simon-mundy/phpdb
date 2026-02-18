<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\Platform\Sql92 as DefaultAdapterPlatform;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

use function get_object_vars;

abstract class AbstractSql implements SqlInterface
{
    protected SqlInterface|PreparableSqlInterface|null $subject = null;

    /**
     * Information used during processing
     *
     * @var array{paramPrefix: string, subselectCount: int}
     */
    public array $processInfo = ['paramPrefix' => '', 'subselectCount' => 0];

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        $adapterPlatform = $adapterPlatform ?: new DefaultAdapterPlatform();

        return $this->buildSqlString($adapterPlatform);
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null
    ): string {
        return '';
    }

    /**
     * Copy variables from the subject into the local properties
     */
    protected function localizeVariables(): void
    {
        if (! $this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            $this->{$name} = $value;
        }
    }
}
