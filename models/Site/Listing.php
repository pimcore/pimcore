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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Site;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Site\Listing\Dao getDao()
 * @method Model\Site[] load()
 * @method Model\Site current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\Site[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $sites = null;

    public function __construct()
    {
        $this->sites = & $this->data;
    }

    /**
     * @return Model\Site[]
     */
    public function getSites()
    {
        return $this->getData();
    }

    /**
     * @param Model\Site[]|null $sites
     *
     * @return static
     */
    public function setSites($sites)
    {
        return $this->setData($sites);
    }
}
