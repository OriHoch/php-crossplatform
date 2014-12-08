<?php

use PhpCrossplatform\PHPCP;

class MainTest extends \PHPUnit_Framework_TestCase {

    public function testExec()
    {
        PHPCP::exec('test 123', '...');
    }

}
