<?php

use PQ\Core\Session;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function testSample() {
        $this->assertFalse("1-2-ka-4" == "4-2-ka-1" , "Testing");
    }
}