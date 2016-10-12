<?php

namespace Pimcore\Model\Element\ElementTrait;

trait GetChildsTrait
{
    /**
     * @deprecated
     * @return mixed
     */
    public function getChilds()
    {
        return $this->getChildren();
    }
}