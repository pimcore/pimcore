<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\ImportConfig;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\ImportConfig\Listing\Dao getDao()
 * @method Model\ImportConfig[] load()
 * @method Model\ImportConfig current()
 *
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $importconfigs = null;

    public function __construct()
    {
        $this->importconfigs = & $this->data;
    }

    /**
     * @return Model\ImportConfig[]
     */
    public function getImportconfigs(): array
    {
        return $this->getData();
    }

    /**
     * @param array $importconfigs
     *
     * @return static
     */
    public function setImportconfigs(array $importconfigs)
    {
        return $this->setData($importconfigs);
    }
}
