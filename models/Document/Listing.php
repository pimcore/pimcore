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

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Paginator\PaginateListingInterface;

/**
 * @method Document[] load()
 * @method Document|false current()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int[] loadIdList()
 * @method \Pimcore\Model\Document\Listing\Dao getDao()
 * @method onCreateQueryBuilder(?callable $callback)
 * @method list<array<string,mixed>> loadIdPathList()
 */
class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    /**
     * Return all documents as Type Document, e.g. for trees and so on there isn't the whole data required
     *
     * @internal
     *
     */
    protected bool $objectTypeDocument = false;

    /**
     * @internal
     *
     */
    protected bool $unpublished = false;

    /**
     * @return Document[]
     */
    public function getDocuments(): array
    {
        return $this->getData();
    }

    public function setDocuments(array $documents): Listing
    {
        return $this->setData($documents);
    }

    /**
     * Checks if the document is unpublished.
     *
     */
    public function getUnpublished(): bool
    {
        return $this->unpublished;
    }

    /**
     * Set the unpublished flag for the document.
     *
     *
     * @return $this
     */
    public function setUnpublished(bool $unpublished): static
    {
        $this->unpublished = $unpublished;

        return $this;
    }

    public function getCondition(): string
    {
        $condition = parent::getCondition();

        if ($condition) {
            if (Document::doHideUnpublished() && !$this->getUnpublished()) {
                $condition = ' (' . $condition . ') AND published = 1';
            }
        } elseif (Document::doHideUnpublished() && !$this->getUnpublished()) {
            $condition = 'published = 1';
        }

        return $condition;
    }

    public function getItems(int $offset, int $itemCountPerPage): array
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }
}
