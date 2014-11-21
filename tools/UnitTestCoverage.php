<?php
$phpunit = simplexml_load_file('build/coverage/index.xml');

$totals = $phpunit->project[0]->directory[0]->totals[0];
$lines = $totals->lines[0];
$methods = $totals->methods[0];
$classes = $totals->classes[0];

if (($lines['percent'] != '100.00%')
    || ($methods['percent'] != '100.00%')
    || ($classes['percent'] != '100.00%')
) {
    echo "Tests DO NOT provide 100% code coverage.\n";
    exit(1);
}

echo "Tests provide 100% code coverage.\n";
