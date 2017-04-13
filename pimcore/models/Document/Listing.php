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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Document;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\AdapterAggregateInterface;

/**
 * @method int load()
 * @method int getTotalCount()
 * @method int getCount()
 * @method int loadIdList()
 */
/**
 * @method \Pimcore\Model\Document\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator, AdapterInterface, AdapterAggregateInterface
{
    /**
     * Return all documents as Type Document. eg. for trees an so on there isn't the whole data required
     *
     * @var bool
     */
    public $objectTypeDocument = false;

    /**
     * Contains the results of the list
     *
     * @var array
     */
    public $documents = null;

    /**
     * @var bool
     */
    public $unpublished = false;

    /**
     * Valid order keys
     *
     * @var array
     */
    public $validOrderKeys = [
        'creationDate',
        'modificationDate',
        'id',
        'key',
        'index'
    ];

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @param $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * Returns documents, also loads the rows if these aren't loaded.
     *
     * @return array
     */
    public function getDocuments()
    {
        if ($this->documents === null) {
            $this->load();
        }

        return $this->documents;
    }

    /**
     * Assign documents to the listing.
     *
     * @param array $documents
     *
     * @return Listing
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;

        return $this;
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
     * @param $unpublished
     *
     * @return bool
     */
    public function setUnpublished($unpublished)
    {
        $this->unpublished = (bool) $unpublished;

        return $this;
    }

    /**
     * Returns the SQL condition value.
     *
     * @return string
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
     * Methods for \Zend_Paginator_Adapter_Interface | AdapterInterface
     */

    /**
     * Returns the total items count.
     *
     * @return int
     */
    public function count()
    {
        return $this->getTotalCount();
    }

    /**
     * Returns the listing based on defined offset and limit as parameters.
     *
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return Listing
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }

    /**
     * @return Listing
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }

    /**
     * Methods for Iterator
     */

    /**
     * Rewind the listing back to te start.
     */
    public function rewind()
    {
        $this->getDocuments();
        reset($this->documents);
    }

    /**
     * Returns the current listing row.
     *
     * @return Document
     */
    public function current()
    {
        $this->getDocuments();
        $var = current($this->documents);

        return $var;
    }

    /**
     * Returns the current listing row key.
     *
     * @return mixed
     */
    public function key()
    {
        $this->getDocuments();
        $var = key($this->documents);

        return $var;
    }

    /**
     * Returns the next listing row key.
     *
     * @return mixed
     */
    public function next()
    {
        $this->getDocuments();
        $var = next($this->documents);

        return $var;
    }

    /**
     * Checks whether the listing contains more entries.
     *
     * @return bool
     */
    public function valid()
    {
        $this->getDocuments();
        $var = $this->current() !== false;

        return $var;
    }
}
