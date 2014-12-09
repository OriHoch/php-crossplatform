<?php

use PhpCrossplatform\PHPCP;
use PhpCrossplatform\execInvalidArgumentsException;


class MainTest extends \PHPUnit_Framework_TestCase {

    protected function _assertDummyExec($output, $env_FOO = null)
    {
        foreach (array(0, 1) as $returnval) {
            $opts = array(
                'args' => array(__DIR__.'/dummy.php', $output, $returnval),
            );
            if (!empty($env_FOO)) {
                $opts['env'] = array('FOO' => $env_FOO);
            }
            foreach (array(false) as $passthru) {
                $opts['passthru'] = $passthru;
                $this->assertEquals($returnval === 0, PHPCP::exec('php', $opts, $result));
                if ($passthru) {
                    $this->assertNull($result->output);
                } else {
                    $expectedOutput = (empty($env_FOO) ? '' : $env_FOO).$output;
                    $this->assertEquals($expectedOutput, implode("\n", $result->output));
                }
                $this->assertEquals($returnval, $result->returnval);
            }
        }
    }

    public function testExec()
    {
        $this->_assertDummyExec('TEST');
    }

    public function testExecSpecialChars()
    {
        $this->_assertDummyExec("\"& '", 0);
        $ok = false;
        try { $this->_assertDummyExec("\"&'\r\n", 0); }
        catch (execInvalidArgumentsException $e) { $ok = true; };
        $this->assertTrue($ok);
        $ok = false;
        try { $this->_assertDummyExec(" x ", 0); }
        catch (execInvalidArgumentsException $e) { $ok = true; };
        $this->assertTrue($ok);
    }

    public function testExecEnv()
    {
        $this->_assertDummyExec('XXX', 'BAR');
        $this->_assertDummyExec("\"& '", "\"& '");
    }

}
