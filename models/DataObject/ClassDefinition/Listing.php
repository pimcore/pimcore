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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Listing\Dao getDao()
 * @method Model\DataObject\ClassDefinition[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $classes = null;

    /**
     * @return Model\DataObject\ClassDefinition[]
     */
    public function getClasses()
    {
        if ($this->classes === null) {
            $this->getDao()->load();
        }

        return $this->classes;
    }

    /**
     * @param $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = $classes;

        return $this;
    }
}
