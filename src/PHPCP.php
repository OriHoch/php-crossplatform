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

    /**
     * Execute an executable
     * $opts array can contain the following keys (all are optional):
     *  args = array of arguments (will be escaped and appended after the executable name)
     *  env = array of key-value pairs of environment variable to set
     *  passthru = if true - will show the output as it comes in (when passing this you can't get the output in the execResult)
     * @param string $cmd the executable to run
     * @param null|array $opts array of options
     * @param null &$result parameter that will contain the execResult object
     * @return bool returns true if command return value is 0
     */
    public static function exec($cmd, $opts = null, &$result = null)
    {
        $result = new execResult();
        $opts = empty($opts) ? array() : $opts;
        if (array_key_exists('args', $opts)) {
            foreach ($opts['args'] as $arg) {
                if (strlen($arg) != strlen(trim($arg))) {
                    throw new execInvalidArgumentsException('arguements cannot start or end with spaces');
                }
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
        // look for special characters
        $ok = true;
        foreach (str_split($cmd) as $char) {
            if (ord($char) < 32 || ord($char) > 126) {
                $ok = false;
                break;
            }
        }
        if (!$ok) {
            throw new execInvalidArgumentsException('command contains invalid ascii character');
        } else {
            if (array_key_exists('passthru', $opts) && $opts['passthru']) {
                passthru($cmd, $result->returnval);
            } else {
                exec($cmd, $result->output, $result->returnval);
            }
            if ($result->returnval === 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    private static function isSurroundedBy($arg, $char)
    {
        return 2 < strlen($arg) && $char === $arg[0] && $char === $arg[strlen($arg) - 1];
    }

};

class execResult {

    public $output = null;
    public $returnval = null;

};

class execInvalidArgumentsException extends \Exception {

}