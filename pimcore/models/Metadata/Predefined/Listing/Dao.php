<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Metadata\Predefined\Listing;

use Pimcore\Model;

class Dao extends Model\Dao\JsonTable {

    /**
     *
     */
    public function configure()
    {
        parent::configure();
        $this->setFile("predefined-asset-metadata");
    }

    /**
     * Loads a list of predefined metadata definitions for the specicified parameters, returns an array of
     * Metadata\Predefined elements
     *
     * @return array
     */
    public function load() {

        $properties = array();
        $definitions = $this->json->fetchAll($this->model->getFilter(), $this->model->getOrder());

        foreach ($definitions as $propertyData) {
            $properties[] = Model\Metadata\Predefined::getById($propertyData["id"]);
        }

        $this->model->setDefinitions($properties);
        return $properties;
    }

    /**
     * @return int
     */
    public function getTotalCount() {

        $data = $this->json->fetchAll($this->model->getFilter(), $this->model->getOrder());
        $amount = count($data);

        return $amount;
    }
}
