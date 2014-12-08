<?php

namespace PhpCrossplatform;

class PHPCP {

    public static function isWindows()
    {
        return defined('PHP_WINDOWS_VERSION_BUILD');
    }

    public static function escapeShellArgument($argument)
    {
        //stolen from symphony (src/Symfony/Component/Process/ProcessUtils.php)
        //Fix for PHP bug #43784 escapeshellarg removes % from given string
        //Fix for PHP bug #49446 escapeshellarg doesn't work on Windows
        //@see https://bugs.php.net/bug.php?id=43784
        //@see https://bugs.php.net/bug.php?id=49446
        if (self::isWindows()) {
            if ('' === $argument) {
                return escapeshellarg($argument);
            }
            $escapedArgument = '';
            $quote =  false;
            foreach (preg_split('/(")/i', $argument, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $part) {
                if ('"' === $part) {
                    $escapedArgument .= '\\"';
                } elseif (self::isSurroundedBy($part, '%')) {
                    // Avoid environment variable expansion
                    $escapedArgument .= '^%"'.substr($part, 1, -1).'"^%';
                } else {
                    // escape trailing backslash
                    if ('\\' === substr($part, -1)) {
                        $part .= '\\';
                    }
                    $quote = true;
                    $escapedArgument .= $part;
                }
            }
            if ($quote) {
                $escapedArgument = '"'.$escapedArgument.'"';
            }
            return $escapedArgument;
        }
        return escapeshellarg($argument);
    }
    
    public static function exec($cmd, $opts = null, &$result = null)
    {
        $result = new execResult();
        $opts = empty($opts) ? array() : $opts;
        if (array_key_exists('args', $opts)) {
            foreach ($opts['args'] as $arg) {
                $cmd .= ' '.self::escapeShellArgument($arg);
            }
        }
        if (self::isWindows() && array_key_exists('env', $opts)) {
            // windows with environment variables - use a special sub-process
            $opts['cmd'] = $cmd;
            $cmd = 'php '.__DIR__.'/../bin/exec.php '.base64_encode(json_encode($opts));
        } elseif (array_key_exists('env', $opts)) {
            // not windows with environment variables
            foreach ($opts['env'] as $k=>$v) {
                $cmd = "{$k}=".escapeshellarg($v).' '.$cmd;
            }
        }
        if (array_key_exists('passthru', $opts) && $opts['passthru']) {
            passthru($cmd, $result->returnval);
        } else {
            exec($ncmd, $result->output, $result->returnval);
        }
        if ($result->returnval === 0) {
            return true;
        } else {
            return false;
        }
    }

};

class execResult {

    public $output = null;
    public $returnval = null;

};
