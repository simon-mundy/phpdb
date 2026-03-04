<?php

declare(strict_types=1);

namespace PhpDb\Sql\Argument;

use PhpDb\Sql\ArgumentInterface;
use PhpDb\Sql\ArgumentType;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

use function str_contains;
use function str_replace;

final readonly class Identifier implements ArgumentInterface
{
    public string $qi;

    public function __construct(
        public string $identifier
    ) {
        $this->qi = str_contains($identifier, '.')
            ? AbstractSqlRenderer::QI_OPEN
                . str_replace('.', AbstractSqlRenderer::QI_SEP, $identifier)
                . AbstractSqlRenderer::QI_CLOSE
            : AbstractSqlRenderer::QI_OPEN . $identifier . AbstractSqlRenderer::QI_CLOSE;
    }

    public function getType(): ArgumentType
    {
        return ArgumentType::Identifier;
    }

    public function getValue(): string
    {
        return $this->identifier;
    }
}
