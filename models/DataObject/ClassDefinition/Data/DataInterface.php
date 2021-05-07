<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;

/**
 * @deprecated not in use anymore, will be removed in Pimcore 10
 */
interface DataInterface
{
    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param null|AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = []);

    /**
     * @deprecated
     *
     * @param string $importValue
     * @param null|Concrete $object
     * @param array $params
     *
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = []);
}
