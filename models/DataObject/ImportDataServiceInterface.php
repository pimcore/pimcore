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

use stdClass;

interface ImportDataServiceInterface
{
    /**
     *
     * @param stdClass $config
     * @param Concrete $object
     * @param array $rowData
     * @param bool $skip
     *
     * @return Concrete
     */
    public function setObjectType($config, $object, $rowData, $skip = false);
}
