<?php

declare(strict_types=1);

namespace PhpDb\Sql\Strategy;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Exception;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;

abstract class AbstractSqlStrategy implements SqlStrategyInterface
{
    protected SqlInterface|PreparableSqlInterface $subject;

    protected array $decorators = [];

    /**
     * {@inheritDoc}
     */
    public function setSubject($subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function setTypeDecorator(string $type, TypeDecoratorInterface $decorator): void
    {
        $this->decorators[$type] = $decorator;
    }

    public function getTypeDecorator(
        PreparableSqlInterface|SqlInterface $subject
    ): TypeDecoratorInterface|PreparableSqlInterface|SqlInterface {
        $subjectClass = $subject::class;
        if (isset($this->decorators[$subjectClass])) {
            $this->decorators[$subjectClass]->setSubject($subject);
            return $this->decorators[$subjectClass];
        }

        return $subject;
    }

    /**
     * @return array|TypeDecoratorInterface[]
     */
    public function getDecorators(): array
    {
        return $this->decorators;
    }

    /**
     * @throws Exception\RuntimeException
     */
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface {
        if (! $this->subject instanceof PreparableSqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\PreparableSqlInterface, thus calling '
                . 'prepareStatement() has no effect'
            );
        }

        $this->getTypeDecorator($this->subject)->prepareStatement($adapter, $statementContainer);

        return $statementContainer;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function getSqlString(?PlatformInterface $adapterPlatform = null): string
    {
        if (! $this->subject instanceof SqlInterface) {
            throw new Exception\RuntimeException(
                'The subject does not appear to implement PhpDb\Sql\SqlInterface, thus calling '
                . 'prepareStatement() has no effect'
            );
        }

        return $this->getTypeDecorator($this->subject)->getSqlString($adapterPlatform);
    }
}
