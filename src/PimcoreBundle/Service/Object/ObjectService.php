<?php

namespace PimcoreBundle\Service\Object;

use Pimcore\Model\Object;

class ObjectService
{
    /**
     * @param $id
     * @return Object\Concrete
     */
    public function get($id)
    {
        return Object\Concrete::getById($id);
    }
}
