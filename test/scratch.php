<?php

use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Select;

require __DIR__ . '/../vendor/autoload.php';

$platform = new class extends Sql92 {
    public function quoteValue(mixed $value): string
    {
        return '\'' . addcslashes((string) $value, "\x00\n\r\\'\"\x1a") . '\'';
    }
};

$warmup     = 5;
$iterations = 20;
/** @var array<string, int|float> $totals */
$totals = [];
$t      = hrtime(true);

for ($i = 0; $i < $warmup + $iterations; $i++) {
    $select = new Select('film');
    $now    = hrtime(true);
    if ($i >= $warmup) {
        $totals['new Select'] = ($totals['new Select'] ?? 0) + ($now - $t);
    }
    $t = $now;

    $select->join('film_actor', 'film.film_id = film_actor.film_id', []);
    $now = hrtime(true);
    if ($i >= $warmup) {
        $totals['join film_actor'] = ($totals['join film_actor'] ?? 0) + ($now - $t);
    }
    $t = $now;

    $select->join('actor', 'film_actor.actor_id = actor.actor_id', ['first_name', 'last_name']);
    $now = hrtime(true);
    if ($i >= $warmup) {
        $totals['join actor'] = ($totals['join actor'] ?? 0) + ($now - $t);
    }
    $t = $now;

    $select->where(['film.rating' => 'PG']);
    $now = hrtime(true);
    if ($i >= $warmup) {
        $totals['where'] = ($totals['where'] ?? 0) + ($now - $t);
    }
    $t = $now;

    $select->order('film.title ASC');
    $now = hrtime(true);
    if ($i >= $warmup) {
        $totals['order'] = ($totals['order'] ?? 0) + ($now - $t);
    }
    $t = $now;

    $select->limit('10');
    $now = hrtime(true);
    if ($i >= $warmup) {
        $totals['limit'] = ($totals['limit'] ?? 0) + ($now - $t);
    }
    $t = $now;

    $sql = $select->getSqlString($platform);
    $now = hrtime(true);
    if ($i >= $warmup) {
        $totals['getSqlString'] = ($totals['getSqlString'] ?? 0) + ($now - $t);
    }
    $t = $now;
}

foreach ($totals as $label => $total) {
    printf("%-40s %8.1f µs\n", $label, $total / $iterations / 1000);
}

echo "\n" . $sql . "\n";
