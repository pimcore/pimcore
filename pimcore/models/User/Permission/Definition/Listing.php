<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\Permission\Definition;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of User\Permission\Definition
     *
     * @var array
     */
    public $definitions = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @param $definitions
     * @return $this
     */
    public function setDefinitions($definitions)
    {
        $this->definitions = $definitions;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }
}
