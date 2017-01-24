<?php

namespace PimcoreBundle\Service\Object;

use Pimcore\Model\Object;

class ObjectService
{
    /**
     * @param $id
     * @return Object\Concrete
     */
    public function getById($id)
    {
        return Object\Concrete::getById($id);
    }
}
