<?php

declare(strict_types=1);

namespace PhpDbTest\TestAsset;

use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;

final class SelectDecorator extends Sql\Select implements PlatformDecoratorInterface
{
    protected Sql\SqlInterface|Sql\PreparableSqlInterface|null $subject = null;

    /**
     * @return $this Provides a fluent interface
     */
    public function setSubject(?object $subject): SelectDecorator
    {
        $this->subject = $subject;
        return $this;
    }

    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?PlatformDecoratorInterface $decorator = null,
    ): string {
        return $this->subject->buildSqlString($platform, $driver, $parameterContainer, $this);
    }
}
