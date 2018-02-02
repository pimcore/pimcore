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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\AbstractObject;

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
     * @param $importValue
     * @param null $object
     * @param array $params
     *
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = []);
}
