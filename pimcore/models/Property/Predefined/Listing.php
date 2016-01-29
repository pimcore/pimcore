<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Property
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Property\Predefined;

use Pimcore\Model;

class Listing extends Model\Listing\JsonListing
{

    /**
     * Contains the results of the list. They are all an instance of Property\Predefined
     *
     * @var array
     */
    public $properties = array();

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return void
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }
}
