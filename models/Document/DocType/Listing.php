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

namespace Pimcore\Model\Document\DocType;

use Pimcore\Model;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Listing\CallableOrderListingInterface;
use Pimcore\Model\Listing\JsonListing;
use Pimcore\Model\Listing\Traits\FilterListingTrait;
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @method \Pimcore\Model\Document\DocType\Listing\Dao getDao()
 * @method int getTotalCount()
 */
class Listing extends JsonListing implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @internal
     *
     * @var array|null
     */
    protected $docTypes = null;

    /**
     * @return \Pimcore\Model\Document\DocType[]
     */
    public function getDocTypes()
    {
        if ($this->docTypes === null) {
            $this->getDao()->loadList();
        }

        return $this->docTypes;
    }

    /**
     * @param array $docTypes
     *
     * @return $this
     */
    public function setDocTypes($docTypes)
    {
        $this->docTypes = $docTypes;

        return $this;
    }

    /**
     * @return Model\Document\DocType[]
     */
    public function load()
    {
        return $this->getDocTypes();
    }
}
