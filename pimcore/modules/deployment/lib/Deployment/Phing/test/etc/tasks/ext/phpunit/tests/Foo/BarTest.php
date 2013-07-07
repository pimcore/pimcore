<?php

class Foo_BarTest extends PHPUnit_Framework_TestCase
{
    public function testGetString()
    {
        $bar = new Foo_Bar();
        
        $this->assertEquals('baz', $bar->getString());
    }
}