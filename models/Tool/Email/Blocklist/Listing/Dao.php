<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Tool\Email\Blocklist\Listing;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\Email\Blocklist\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of static routes for the specicifies parameters, returns an array of Tool\Email\Blocklist elements
     *
     */
    public function load(): array
    {
        $addressData = $this->db->fetchFirstColumn('SELECT address FROM email_blocklist' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        $addresses = [];
        foreach ($addressData as $data) {
            if ($address = Model\Tool\Email\Blocklist::getByAddress($data)) {
                $addresses[] = $address;
            }
        }

        $this->model->setItems($addresses);

        return $addresses;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM email_blocklist ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (Exception $e) {
            return 0;
        }
    }
}
