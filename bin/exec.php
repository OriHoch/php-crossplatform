<?php
/**
 * Execute a command with environment variables on windows
 */

/* 
require_once __DIR__.'/../src/PHPCP.php';
use PhpCrossplatform\PHPCP;
*/

if ((count($argv)) < 3) {
	echo "Usage: php {$argv[0]} <<BASE64_ENCODED_JSON_ENCODED_OPTS>> [--debug]\n";
	exit(1);
} else {
	$data = json_decode(base64_decode($argv[1]), true);
    $debug = in_array('--debug', $argv);
    if (!array_key_exists('cmd', $data)) {
        echo "'cmd' key must exist\n";
		exit(1);
	} else {
        if ($debug) {
            var_dump($data);
        };
		$cmd = $data['cmd'];
		$env = array_key_exists('env', $data) ? $data['env'] : array();
        $passthru = array_key_exists('passthru', $data) ? $data['passthru'] : false;
		foreach ($env as $k=>$v) {
			putenv($k.'='.$v);
		};
		if ($passthru) {
            passthru($cmd, $returnvar);
			exit($returnvar);
        } else {
            exec($cmd, $output, $returnvar);
			echo implode("\n", $output);
			exit($returnvar);
        };
	};
};
