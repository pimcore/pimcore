<?php
/**
 * Created by PhpStorm.
 * User: mrudnicki
 * Date: 10/12/2016
 * Time: 8:56 AM
 */
namespace Pimcore\Model\Element\ElementTrait;

trait HasChildsTrait
{
    /**
     * @deprecated
     * @return mixed
     */
    public function hasChilds()
    {
        return $this->hasChildren();
    }
}