<?php

namespace AppBundle\EventListener;

class TestListener {

    public function onObjectPreUpdate($event) {

        $foo = "bar";
    }
}
