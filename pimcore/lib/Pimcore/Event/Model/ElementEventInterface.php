<?php

namespace Pimcore\Event\Model;

use Pimcore\Model\Element\ElementInterface;

interface ElementEventInterface {

    /**
     * @return ElementInterface
     */
    public function getElement();
}
