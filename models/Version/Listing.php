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
 * @package    Schedule
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Version;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Version\Listing\Dao getDao()
 * @method array loadIdList()
 * @method Model\Version[] load()
 * @method Model\Version current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var bool
     */
    protected $loadDrafts = false;

    /**
     * @return bool
     */
    public function getLoadDrafts(): bool
    {
        return $this->loadDrafts;
    }

    /**
     * @param bool $loadDrafts
     * @return $this
     */
    public function setLoadDrafts($loadDrafts)
    {
        $this->loadDrafts = $loadDrafts;
        return $this;
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions()
    {
        return $this->getData();
    }

    /**
     * @param Model\Version[]|null $versions
     *
     * @return static
     */
    public function setVersions($versions)
    {
        return $this->setData($versions);
    }
}
