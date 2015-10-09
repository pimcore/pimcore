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


namespace OnlineShop\Framework\Impl\OrderManager;


/**
 * Class AbstractListItem
 * template method pattern
 */
abstract class AbstractOrderListItem
{
    /**
     * @var array
     */
    protected $resultRow;


    /**
     * @param array $resultRow
     */
    public function __construct(array $resultRow)
    {
        $this->resultRow = $resultRow;
    }


    /**
     * @return int
     */
    abstract function getId();
}