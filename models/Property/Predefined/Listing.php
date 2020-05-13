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
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Property\Predefined;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Property\Predefined\Listing\Dao getDao()
 * @method \Pimcore\Model\Property\Predefined[] load()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\JsonListing
{
    /**
     * @var array|null
     */
    protected $properties = null;

    /**
     * @return \Pimcore\Model\Property\Predefined[]
     */
    public function getProperties()
    {
        if ($this->properties === null) {
            $this->getDao()->load();
        }

        return $this->properties;
    }

    /**
     * @param \Pimcore\Model\Property\Predefined[] $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }
}
