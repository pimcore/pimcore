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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Property\Predefined;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Property\Predefined\Listing\Dao getDao()
 */
class Listing extends Model\Listing\JsonListing
{

    /**
     * Contains the results of the list. They are all an instance of Property\Predefined
     *
     * @var array
     */
    public $properties = [];

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }
}
