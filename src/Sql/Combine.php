<?php

declare(strict_types=1);

namespace PhpDb\Sql;

use Override;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Part\SqlProcessor;
use PhpDb\Sql\Platform\AbstractPlatform as SqlPlatform;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function implode;
use function is_array;

/**
 * Combine SQL statement - allows combining multiple select statements into one
 */
class Combine extends AbstractPreparableSql
{
    final public const COLUMNS = 'columns';

    final public const COMBINE = 'combine';

    final public const COMBINE_UNION = 'UNION';

    final public const COMBINE_EXCEPT = 'EXCEPT';

    final public const COMBINE_INTERSECT = 'INTERSECT';

    /** @var array<array{select: Select, type: string, modifier: string}> */
    private array $combine = [];

    public function __construct(
        Select|array|null $select = null,
        string $type = self::COMBINE_UNION,
        string $modifier = ''
    ) {
        if ($select) {
            $this->combine($select, $type, $modifier);
        }
    }

    /**
     * Create combine clause
     *
     * @throws Exception\InvalidArgumentException
     */
    public function combine(Select|array $select, string $type = self::COMBINE_UNION, string $modifier = ''): static
    {
        if (is_array($select)) {
            foreach ($select as $combine) {
                if ($combine instanceof Select) {
                    $combine = [$combine];
                }

                $this->combine(
                    $combine[0],
                    $combine[1] ?? $type,
                    $combine[2] ?? $modifier
                );
            }

            return $this;
        }

        $this->combine[] = [
            'select'   => $select,
            'type'     => $type,
            'modifier' => $modifier,
        ];
        return $this;
    }

    /**
     * Create union clause
     */
    public function union(Select|array $select, string $modifier = ''): static
    {
        return $this->combine($select, self::COMBINE_UNION, $modifier);
    }

    /**
     * Create except clause
     */
    public function except(Select|array $select, string $modifier = ''): static
    {
        return $this->combine($select, self::COMBINE_EXCEPT, $modifier);
    }

    /**
     * Create intersect clause
     */
    public function intersect(Select|array $select, string $modifier = ''): static
    {
        return $this->combine($select, self::COMBINE_INTERSECT, $modifier);
    }

    /**
     * Build sql string
     */
    #[Override]
    public function buildSqlString(
        PlatformInterface $platform,
        ?DriverInterface $driver = null,
        ?ParameterContainer $parameterContainer = null,
        ?SqlPlatform $sqlPlatform = null,
    ): string {
        if (! $this->combine) {
            return '';
        }

        $processor = new SqlProcessor($platform, $driver, $parameterContainer, $sqlPlatform);
        $processor->setParamPrefix($this->processInfo['paramPrefix']);

        $parts = [];
        foreach ($this->combine as $i => $combine) {
            $select = $processor->processSubSelect($combine['select']);

            if ($i === 0) {
                $parts[] = "({$select})";
            } else {
                $type    = $combine['modifier']
                    ? "{$combine['type']} {$combine['modifier']}"
                    : $combine['type'];
                $parts[] = "{$type} ({$select})";
            }
        }

        return implode(' ', $parts);
    }

    public function alignColumns(): static
    {
        if (! $this->combine) {
            return $this;
        }

        $allColumns = [];
        foreach ($this->combine as $combine) {
            $allColumns = array_merge(
                $allColumns,
                $combine['select']->getRawState(self::COLUMNS)
            );
        }

        foreach ($this->combine as $combine) {
            $combineColumns = $combine['select']->getRawState(self::COLUMNS);
            $aligned        = [];
            foreach (array_keys($allColumns) as $alias) {
                $aligned[$alias] = $combineColumns[$alias] ?? new Predicate\Expression('NULL');
            }

            $combine['select']->columns($aligned, false);
        }

        return $this;
    }

    /**
     * Get raw state
     */
    public function getRawState(?string $key = null): mixed
    {
        $rawState = [
            self::COMBINE => $this->combine,
            self::COLUMNS => $this->combine
                                ? $this->combine[0]['select']->getRawState(self::COLUMNS)
                                : [],
        ];
        return isset($key) && array_key_exists($key, $rawState) ? $rawState[$key] : $rawState;
    }
}
