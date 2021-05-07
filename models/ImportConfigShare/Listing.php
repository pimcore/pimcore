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

namespace Pimcore\Model\ImportConfigShare;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\ImportConfigShare\Listing\Dao getDao()
 * @method Model\ImportConfigShare[] load()
 * @method Model\ImportConfigShare current()
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
    protected $importConfigShares = null;

    public function __construct()
    {
        $this->importConfigShares = & $this->data;
    }

    /**
     * @return Model\ImportConfigShare[]
     */
    public function getImportConfigShares(): array
    {
        return $this->getData();
    }

    /**
     * @param array $importConfigShares
     */
    public function setImportConfigShares(array $importConfigShares)
    {
        return $this->setData($importConfigShares);
    }
}
