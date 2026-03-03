<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use PhpDb\Sql\Argument\Literal;
use PhpDb\Sql\Exception\RuntimeException;
use PhpDb\Sql\Platform\AbstractSqlRenderer;

use function count;
use function explode;

trait TokenizesExpressionTrait
{
    private ?array $tokens = null;

    protected function tokenize(string $expression, array $arguments): void
    {
        if ($arguments === []) {
            $this->tokens = [new Literal($expression)];
            return;
        }

        $fragments = explode('?', $expression);

        if (count($fragments) - 1 !== count($arguments)) {
            throw new RuntimeException(
                'The number of replacements in the expression does not match the number of parameters'
            );
        }

        $tokens = [];
        foreach ($fragments as $i => $fragment) {
            if ($fragment !== '') {
                $tokens[] = new Literal($fragment);
            }
            if (isset($arguments[$i])) {
                $tokens[] = $arguments[$i];
            }
        }
        $this->tokens = $tokens;
    }

    protected function invalidateTokens(): void
    {
        $this->tokens = null;
    }

    protected function renderTokens(AbstractSqlRenderer $renderer, string $paramPrefix, int &$paramIndex): string
    {
        $sql = '';
        foreach ($this->tokens as $token) {
            $sql .= $renderer->renderArgument($token, $paramPrefix, $paramIndex);
        }
        return $sql;
    }
}
