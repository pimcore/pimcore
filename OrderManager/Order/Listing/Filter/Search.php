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
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\Order\Listing\Filter;

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