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

namespace Pimcore\Model\DataObject;

interface LazyLoadedFieldsInterface
{
    /**
     * @param $key
     */
    public function addLazyKey($key);

    /**
     * @param $key
     */
    public function removeLazyKey($key);

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasLazyKey($key);

    /**
     * @return bool
     */
    public function hasLazyKeys();

    /**
     * @return array
     */
    public function getLazyKeys();
}
