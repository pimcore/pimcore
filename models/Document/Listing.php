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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Paginator\PaginateListingInterface;

/**
 * @method Document[] load()
 * @method Document current()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int[] loadIdList()
 * @method \Pimcore\Model\Document\Listing\Dao getDao()
 * @method onCreateQuery(callable $callback)
 * @method onCreateQueryBuilder(?callable $callback)
 * @method array loadIdPathList()
 */
class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    /**
     * Return all documents as Type Document. eg. for trees an so on there isn't the whole data required
     *
     * @internal
     *
     * @var bool
     */
    protected $objectTypeDocument = false;

    /**
     * @internal
     *
     * @var bool
     */
    protected $unpublished = false;

    /**
     * @return Document[]
     */
    public function getDocuments()
    {
        return $this->getData();
    }

    /**
     * @param array $documents
     *
     * @return Listing
     */
    public function setDocuments($documents)
    {
        return $this->setData($documents);
    }

    /**
     * Checks if the document is unpublished.
     *
     * @return bool
     */
    public function getUnpublished()
    {
        return $this->unpublished;
    }

    /**
     * Set the unpublished flag for the document.
     *
     * @param bool $unpublished
     *
     * @return $this
     */
    public function setUnpublished($unpublished)
    {
        $this->unpublished = (bool) $unpublished;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCondition()
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

    /**
     *
     * Methods for AdapterInterface
     */

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getTotalCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }
}
