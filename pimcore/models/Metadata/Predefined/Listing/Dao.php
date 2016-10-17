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
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Metadata\Predefined\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Metadata\Predefined\Listing $model
 */
class Dao extends Model\Dao\PhpArrayTable
{

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
    public function load()
    {
        $properties = [];
        $definitions = $this->db->fetchAll($this->model->getFilter(), $this->model->getOrder());

        foreach ($definitions as $propertyData) {
            $properties[] = Model\Metadata\Predefined::getById($propertyData["id"]);
        }

        $this->model->setDefinitions($properties);

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
