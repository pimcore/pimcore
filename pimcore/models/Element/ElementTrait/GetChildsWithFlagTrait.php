<?php
/**
 * Created by PhpStorm.
 * User: mrudnicki
 * Date: 10/12/2016
 * Time: 8:52 AM
 */
namespace Pimcore\Model\Element\ElementTrait;

trait GetChildsWithFlagTrait
{
    /**
     * @deprecated
     * @return mixed
     */
    public function getChilds($unpublished = false)
    {
        return $this->getChildren($unpublished);
    }
}