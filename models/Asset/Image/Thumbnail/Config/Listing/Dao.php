<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Asset\Image\Thumbnail\Config\Listing;

use Pimcore\Db\PimcoreConfigStorage;
use Pimcore\Model;
use Pimcore\Model\Asset\Image\Thumbnail\Config;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Image\Thumbnail\Config\Listing $model
 * @property PimcoreConfigStorage $db
 */
class Dao extends Model\Dao\PimcoreConfigBag
{
    public function configure()
    {
        parent::configure();
        $this->setContext('image-thumbnails');
    }

    /**
     * @return array
     */
    public function load()
    {
        $configs = [];
        $configData = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        foreach ($configData as $key => $propertyData) {
            $config = Config::getByName($key);
            $config->setWriteable($propertyData['writeable'] ?? false);
            $configs[] = $config;
        }

        $this->model->setThumbnails($configs);

        return $configs;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $data = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());
        $amount = count($data);

        return $amount;
    }
}
