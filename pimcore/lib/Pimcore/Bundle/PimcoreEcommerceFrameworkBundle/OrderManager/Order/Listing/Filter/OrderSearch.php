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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\Order\Listing\Filter;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderListFilter;

class OrderSearch implements IOrderListFilter
{
    /**
     * @var string
     */
    protected $keyword;
    

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // init
        $query = $orderList->getQuery();


        if ($this->getKeyword()) {
            $condition = <<<'SQL'
0
OR `order`.ordernumber like ?
OR `order`.comment like ?

OR `order`.customerFirstName like ?
OR `order`.customerLastName like ?
OR `order`.customerCompany like ?
OR `order`.customerStreet like ?
OR `order`.customerZip like ?
OR `order`.customerCity like ?
OR `order`.customerCountry like ?

OR `order`.deliveryFirstName like ?
OR `order`.deliveryLastName like ?
OR `order`.deliveryCompany like ?
OR `order`.deliveryStreet like ?
OR `order`.deliveryZip like ?
OR `order`.deliveryCity like ?
OR `order`.deliveryCountry like ?
SQL;

            $query->where($condition, '%' . $this->getKeyword() . '%');
        }
    }

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @param string $keyword
     *
     * @return $this
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }
}
