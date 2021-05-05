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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Concrete;

interface QueryResourcePersistenceAwareInterface
{
    /**
     * Returns the data which should be stored in the query columns
     *
     * @param mixed $data
     * @param null|Concrete $object
     * @param mixed $params
     *
     * @return mixed
     *
     * abstract public function getDataForQueryResource($data);
     */
    public function getDataForQueryResource($data, $object = null, $params = []);

    /**
     * @return string|array|null
     */
    public function getQueryColumnType();
}
