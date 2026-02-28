<?php

declare(strict_types=1);

namespace PhpDb\Sql\Part;

use function implode;

final class SqlFragment
{
    private array $parts = [];

    public static function of(string $keyword): self
    {
        $f = new self();
        $f->parts[] = $keyword;
        return $f;
    }

    public function part(?string $part): self
    {
        if ($part !== null) {
            $this->parts[] = $part;
        }
        return $this;
    }

    public function wrap(?string $suffix): self
    {
        if ($suffix !== null) {
            $this->parts = ['( ' . implode(' ', $this->parts) . ' )', $suffix];
        }
        return $this;
    }

    public function __toString(): string
    {
        return implode(' ', $this->parts);
    }
}
