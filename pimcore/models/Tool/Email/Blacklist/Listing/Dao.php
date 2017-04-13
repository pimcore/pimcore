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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Email\Blacklist\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Tool\Email\Blacklist\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Tool\Email\Blacklist elements
     *
     * @return array
     */
    public function load()
    {
        $addressData = $this->db->fetchCol('SELECT address FROM email_blacklist' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $addresses = [];
        foreach ($addressData as $data) {
            if ($address = Model\Tool\Email\Blacklist::getByAddress($data)) {
                $addresses[] = $address;
            }
        }

        $this->model->setItems($addresses);

        return $addresses;
    }

    /**
     * @return int
     *
     * @todo: $amount could not be defined, so this could cause an issue
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM email_blacklist ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
