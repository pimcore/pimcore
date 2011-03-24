<?php


class Pimcore_Controller_Action_Helper_Json extends Zend_Controller_Action_Helper_Json {

    public function direct($data, $direct = true) {

        // hack for FCGI because ZF doesn't care of duplicate headers
        $this->getResponse()->clearHeader("Content-Type");

        $this->suppressExit = !$direct;

        $d = $this->sendJson($data);
        return $d;
    }

}
