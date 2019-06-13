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

namespace Pimcore\Model\GridConfig;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\GridConfig\Listing\Dao getDao()
 * @method Model\GridConfig[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $gridConfigs = null;

    /**
     * @return Model\GridConfig[]
     */
    public function getGridConfigs()
    {
        if ($this->gridConfigs === null) {
            $this->getDao()->load();
        }

        return $this->gridConfigs;
    }

    /**
     * @param $gridConfigs
     *
     * @return $this
     */
    public function setGridConfigs($gridConfigs)
    {
        $this->gridConfigs = $gridConfigs;

        return $this;
    }
}
