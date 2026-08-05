<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Document\DocType;

use Pimcore\Model;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Listing\CallableFilterListingInterface;
use Pimcore\Model\Listing\CallableOrderListingInterface;
use Pimcore\Model\Listing\Traits\FilterListingTrait;
use Pimcore\Model\Listing\Traits\OrderListingTrait;

/**
 * @method \Pimcore\Model\Document\DocType\Listing\Dao getDao()
 * @method int getTotalCount()
 */
class Listing extends AbstractModel implements CallableFilterListingInterface, CallableOrderListingInterface
{
    use FilterListingTrait;
    use OrderListingTrait;

    /**
     * @internal
     *
     */
    protected ?array $docTypes = null;

    /**
     * @return \Pimcore\Model\Document\DocType[]
     */
    public function getDocTypes(): array
    {
        if ($this->docTypes === null) {
            $this->getDao()->loadList();
        }

        return $this->docTypes;
    }

    public function setDocTypes(array $docTypes): static
    {
        $this->docTypes = $docTypes;

        return $this;
    }

    /**
     * @return Model\Document\DocType[]
     */
    public function load(): array
    {
        return $this->getDocTypes();
    }
}
