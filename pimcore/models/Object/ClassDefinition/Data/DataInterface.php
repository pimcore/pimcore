<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

interface DataInterface
{

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param null|AbstractObject $object
     * @param mixed $params
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = array());

    /**
     * @param $importValue
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = array());
}
