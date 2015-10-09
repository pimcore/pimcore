<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_VoucherService_Token_List_Resource extends \Pimcore\Model\Listing\Resource\AbstractResource
{

    public function load()
    {
        $tokens = array();

        $unitIds = $this->db->fetchAll("SELECT * FROM " .
            OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME .
            $this->getCondition() .
            $this->getOrder() .
            $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($unitIds as $row) {
            $item = new OnlineShop_Framework_VoucherService_Token();
            $item->getResource()->assignVariablesToModel($row);
            $tokens[] = $item;
        }

        $this->model->setTokens($tokens);

        return $tokens;
    }

    public function getTotalCount()
    {
        try {
            $amount = (int)$this->db->fetchOne("SELECT COUNT(*) as amount FROM " .
                OnlineShop_Framework_VoucherService_Token_Resource::TABLE_NAME .
                $this->getCondition(),
                $this->model->getConditionVariables());
        } catch (\Exception $e) {
            return false;
        }

        return $amount;
    }

}