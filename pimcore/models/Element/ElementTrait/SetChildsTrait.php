<?php

namespace Pimcore\Model\Element\ElementTrait;

trait SetChildsTrait
{
    /**
     * @deprecated
     * @param $childs
     */
    public function setChilds($childs)
    {
        return $this->setChildren($childs);
    }
}