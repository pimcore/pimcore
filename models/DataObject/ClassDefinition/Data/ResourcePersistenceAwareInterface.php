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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Concrete;

interface ResourcePersistenceAwareInterface
{
    /**
     * Returns the the data that should be stored in the resource
     *
     * @param mixed $data
     * @param null|Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDataForResource($data, $object = null, $params = []);

    /**
     * Convert the saved data in the resource to the internal eg. Image-Id to Asset\Image object, this is the inverted getDataForResource()
     *
     * @param mixed $data
     * @param null|Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDataFromResource($data, $object = null, $params = []);

    /**
     * @return string|array
     */
    public function getColumnType();
}
