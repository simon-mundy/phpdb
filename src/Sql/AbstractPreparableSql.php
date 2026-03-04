<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\StatementContainerInterface;
use PhpDb\Sql\Platform\AbstractSqlRenderer;
use PhpDb\Sql\Platform\Sql92Renderer;

use function strtr;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    #[Override]
    public function prepareStatement(
        AdapterInterface $adapter,
        StatementContainerInterface $statementContainer
    ): StatementContainerInterface {
        $parameterContainer = $statementContainer->getParameterContainer();

        if (! $parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();

            $statementContainer->setParameterContainer($parameterContainer);
        }

        $renderer = (new Sql92Renderer())->init(
            $adapter->getPlatform(),
            $adapter->getDriver(),
            $parameterContainer
        );

        $statementContainer->setSql(
            strtr(
                $this->buildSqlString($renderer),
                AbstractSqlRenderer::QI_PAIR,
                $renderer->quoteChars
            )
        );

        return $statementContainer;
    }
}
