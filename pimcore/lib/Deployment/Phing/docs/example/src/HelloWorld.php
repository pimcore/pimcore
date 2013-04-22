<?php

    /**
     * The Hello World class!
     *
     * @author Michiel Rook
     * @version $Id: 9d3de78ba8667dc31d1f7dd71af7638adba505df $
     * @package hello.world
     */
    class HelloWorld
    {
        public function foo($silent = true)
        {
            if ($silent) {
                return;
            }
            return 'foo';
        }

        function sayHello()
        {
            return "Hello World!";
        }
    };

?>
