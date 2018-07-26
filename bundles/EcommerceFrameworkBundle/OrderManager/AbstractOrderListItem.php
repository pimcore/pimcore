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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

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
    abstract public function getId();
}
