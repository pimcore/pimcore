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


namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

/**
 * Search filter with flexible column definition
 */
class Search extends AbstractSearch
{
    /**
     * Search column
     * @var string
     */
    protected $column;

    /**
     * @param string $value
     * @param string $column
     */
    public function __construct($value, $column)
    {
        parent::__construct($value);
        $this->column = $column;
    }

    protected function getConditionColumn()
    {
        return $this->column;
    }
}