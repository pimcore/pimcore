<?php
namespace Pimcore\Model\Element\ElementTrait;

trait GetHasChildsInObjectsTrait
{
    /**
     * @deprecated
     * @param array $objectTypes
     * @param bool $unpublished
     * @return mixed
     */
    public function getChilds($objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $unpublished = false)
    {
        return $this->getChildren($objectTypes, $unpublished);
    }


    public function hasChilds($objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER])
    {
        return $this->hasChildren($objectTypes);
    }
}