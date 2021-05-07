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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition[] load()
 * @method Model\DataObject\ClassDefinition current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\DataObject\ClassDefinition[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $classes = null;

    public function __construct()
    {
        $this->classes = & $this->data;
    }

    /**
     * @return Model\DataObject\ClassDefinition[]
     */
    public function getClasses()
    {
        return $this->getData();
    }

    /**
     * @param Model\DataObject\ClassDefinition[]|null $classes
     *
     * @return static
     */
    public function setClasses($classes)
    {
        return $this->setData($classes);
    }
}
