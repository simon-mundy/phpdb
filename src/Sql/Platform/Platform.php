<?php

declare(strict_types=1);

namespace PhpDb\Sql\Platform;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

final class Platform extends AbstractPlatform
{
    protected PlatformInterface $defaultPlatform;

    public function __construct(PlatformInterface $platform)
    {
        $this->defaultPlatform = $platform;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        if (! $this->subject instanceof SqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\SqlInterface, thus calling '
                    . 'getSqlString() has no effect',
            );
        }

        return $this->getTypeDecorator($this->subject)->getSqlString(
            $this->resolvePlatform($adapterPlatform),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer,
    ): StatementContainerInterface {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\PreparableSqlInterface, thus calling '
                    . 'prepareStatement() has no effect',
            );
        }

        $this->getTypeDecorator($this->subject)->prepareStatement($adapter, $statementContainer);

        return $statementContainer;
    }

    protected function getDefaultPlatform(): PlatformInterface
    {
        return $this->defaultPlatform;
    }

    protected function resolvePlatform(PlatformInterface|AdapterInterface|null $adapterOrPlatform): PlatformInterface
    {
        if (null === $adapterOrPlatform) {
            return $this->getDefaultPlatform();
        }

        if ($adapterOrPlatform instanceof AdapterInterface) {
            return $adapterOrPlatform->getPlatform();
        }

        return $adapterOrPlatform;
    }
}
