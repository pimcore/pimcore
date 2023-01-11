<?php
declare(strict_types=1);

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
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Document\DocType;
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
     * @var array|null
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

    /**
     * Sorts DocTypes by priority and falls back to name in case they are equal
     *
     * @example $listing->setOrder([[Listing::class, 'sortByPriority']]);
     *
     * @param DocType $a
     * @param DocType $b
     *
     * @return int
     */
    public static function sortByPriority(DocType $a, DocType $b): int
    {
        if ($a->getPriority() === $b->getPriority()) {
            return \strcasecmp($a->getName(), $b->getName());
        }

        return $a->getPriority() <=> $b->getPriority();
    }
}
