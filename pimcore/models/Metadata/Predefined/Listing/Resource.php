<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Metadata\Predefined\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of predefined metadata definitions for the specicified parameters, returns an array of
     * Metadata\Predefined elements
     *
     * @return array
     */
    public function load() {

        $properties = array();
        $definitions = $this->db->fetchCol("SELECT id FROM assets_metadata_predefined" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($definitions as $propertyData) {
            $properties[] = Model\Metadata\Predefined::getById($propertyData);
        }

        $this->model->setDefinitions($properties);
        return $properties;
    }

    public function getTotalCount() {

        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM assets_metadata_predefined " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {

        }

        return $amount;
    }

}
