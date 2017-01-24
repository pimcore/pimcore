<?php

namespace PimcoreBundle\Service\Asset;

use Pimcore\Model\Asset;

class AssetService
{
    /**
     * @param $id
     * @return Asset
     */
    public function get($id)
    {
        return Asset::getById($id);
    }
}
