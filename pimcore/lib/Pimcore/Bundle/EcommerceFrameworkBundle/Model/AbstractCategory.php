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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

/**
 * Abstract base class for pimcore objects who should be used as product categories in the online shop framework
 */
class AbstractCategory extends \Pimcore\Model\Object\Concrete
{

    /**
     * @static
     * @param int $id
     * @return null|\Pimcore\Model\Object\AbstractObject
     */
    public static function getById($id)
    {
        $object = \Pimcore\Model\Object\AbstractObject::getById($id);

        if ($object instanceof AbstractCategory) {
            return $object;
        }

        return null;
    }

    /**
     * defines if product is visible in product index queries for parent categories of product category.
     * e.g.
     *   football
     *     - shoes
     *     - shirts
     *
     * all products if category shoes or shirts are visible in queries for category football
     *
     * @return bool
     */
    public function getOSProductsInParentCategoryVisible()
    {
        return true;
    }
}
