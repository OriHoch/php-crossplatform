<?php

/*
 * Dummy exec - just dumps back the parameters
 */

if (count($argv) != 3) {
    echo "usage: {$argv[0]} OUTPUT RETURN_VALUE\n";
    exit(1);
} else {
    if ($tmp = getenv('FOO')) {
        echo $tmp;
    };
    echo $argv[1];
    exit((int)$argv[2]);
}
