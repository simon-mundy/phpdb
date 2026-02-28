<?php

use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Join;
use PhpDb\Sql\Select;

require __DIR__ . '/../vendor/autoload.php';

$platform = new class extends Sql92 {
    public function quoteValue(mixed $value): string
    {
        return '\'' . addcslashes((string) $value, "\x00\n\r\\'\"\x1a") . '\'';
    }
};

$t   = hrtime(true);
$i   = 0;
$lap = function (string $label) use (&$t, &$i) {
    $now = hrtime(true);
    if ($i === 4) { // @phpstan-ignore identical.alwaysFalse
        printf("%-40s %8.1f µs\n", $label, ($now - $t) / 1000);
    }
    $t = $now;
};

for ($i = 0; $i < 5; $i++) {
    $select = new Select('film');
    $lap('new Select');
    $select->join('film_actor', 'film.film_id = film_actor.film_id', []);
    $lap('join film_actor');
    $select->join('actor', 'film_actor.actor_id = actor.actor_id', ['first_name', 'last_name']);
    $lap('join actor');
    $select->where(['film.rating' => 'PG']);
    $lap('where');
    $select->order('film.title ASC');
    $lap('order');
    $select->limit('10');
    $lap('limit');
    $sql = $select->getSqlString($platform);
    $lap('getSqlString');
}

echo "\n" . $sql . "\n";

echo "\n=== Isolated: Joins->join() first vs second call ===\n";
for ($i = 0; $i < 5; $i++) {
    $joins = new Join();

    $t = hrtime(true);
    $joins->join('film_actor', 'film.film_id = film_actor.film_id', [], 'INNER');
    $now   = hrtime(true);
    $first = ($now - $t) / 1000;

    $t = hrtime(true);
    $joins->join('actor', 'film_actor.actor_id = actor.actor_id', ['first_name', 'last_name'], 'INNER');
    $now    = hrtime(true);
    $second = ($now - $t) / 1000;

    printf("Pass %d:  1st %5.1f µs   2nd %5.1f µs\n", $i, $first, $second);
}
