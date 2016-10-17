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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Classificationstore\KeyConfig;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Classificationstore\KeyConfig\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of Classificationstore_KeyConfig
     *
     * @var array
     */
    public $list = [];

    /** @var  bool */
    public $includeDisabled;

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
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array
     * @return $this
     */
    public function setList($theList)
    {
        $this->list = $theList;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIncludeDisabled()
    {
        return $this->includeDisabled;
    }

    /**
     * @param boolean $includeDisabled
     */
    public function setIncludeDisabled($includeDisabled)
    {
        $this->includeDisabled = $includeDisabled;
    }
}
