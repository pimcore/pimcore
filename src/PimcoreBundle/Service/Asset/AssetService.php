<?php

namespace PimcoreBundle\Service\Asset;

use Pimcore\Model\Asset;

class AssetService
{
    /**
     * @param $id
     * @return Asset
     */
    public function getById($id)
    {
        return Asset::getById($id);
    }
}
