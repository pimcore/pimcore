<?php

    require_once "src/HelloWorld.php";

    /**
    * Test class for HelloWorld
    *
    * @author Michiel Rook
    * @version $Id: 20a847444b498a7343ebbfb04e02213f3b56462c $
    * @package hello.world
    */
    class HelloWorldTest extends PHPUnit_Framework_TestCase
    {
        public function testSayHello()
        {
            $hello = new HelloWorld();
            $this->assertEquals("Hello World!", $hello->sayHello());
        }
    }

?>
