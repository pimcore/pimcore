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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Listing;

class Dao extends \Pimcore\Model\Listing\Dao\AbstractDao
{
    public function load()
    {
        $tokens = [];

        $unitIds = $this->db->fetchAll("SELECT * FROM " .
            \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($unitIds as $row) {
            $item = new \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token();
            $item->getDao()->assignVariablesToModel($row);
            $tokens[] = $item;
        }

        $this->model->setTokens($tokens);

        return $tokens;
    }

    public function getTotalCount()
    {
        try {
            $amount = (int)$this->db->fetchOne("SELECT COUNT(*) as amount FROM " .
                \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME .
                $this->getCondition(),
                $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return false;
        }

        return $amount;
    }
}
