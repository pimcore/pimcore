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
 * @method Model\GridConfig current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\GridConfig[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $gridConfigs = null;

    public function __construct()
    {
        $this->gridConfigs = & $this->data;
    }

    /**
     * @return Model\GridConfig[]
     */
    public function getGridConfigs()
    {
        return $this->getData();
    }

    /**
     * @param Model\GridConfig[]|null $gridConfigs
     *
     * @return static
     */
    public function setGridConfigs($gridConfigs)
    {
        return $this->setData($gridConfigs);
    }
}
