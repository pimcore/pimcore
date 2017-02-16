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
 * @package    Site
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Site;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Site\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of Staticroute
     *
     * @var array
     */
    public $sites = [];

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     * @param $key
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param array $sites
     * @return $this
     */
    public function setSites($sites)
    {
        $this->sites = $sites;

        return $this;
    }
}
