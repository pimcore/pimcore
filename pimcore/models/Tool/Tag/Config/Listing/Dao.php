<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Property
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Tag\Config\Listing;

use Pimcore\Model;
use Pimcore\Model\Tool\Tag\Config;

/**
 * @property \Pimcore\Model\Tool\Tag\Config\Listing $model
 */
class Dao extends Model\Dao\PhpArrayTable
{

    /**
     *
     */
    public function configure()
    {
        parent::configure();
        $this->setFile("tag-manager");
    }

    /**
     * @return array
     */
    public function load()
    {
        $properties = [];
        $propertiesData = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        foreach ($propertiesData as $propertyData) {
            $properties[] = Config::getByName($propertyData["id"]);
        }

        $this->model->setTags($properties);

        return $properties;
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
