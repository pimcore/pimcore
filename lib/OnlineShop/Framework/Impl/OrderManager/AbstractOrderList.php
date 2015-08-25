<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 07.04.2015
 * Time: 16:47
 */

namespace OnlineShop\Framework\Impl\OrderManager;

use Zend_Paginator_Adapter_Interface;
use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListItem;

use OnlineShop_Framework_AbstractOrder;

use Pimcore\Resource;


abstract class AbstractOrderList implements IOrderList
{
    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $limit = 30;

    /**
     * @var int
     */
    protected $rowCount = 0;

    /**
     * @var string
     */
    protected $listType = self::LIST_TYPE_ORDER;

    /**
     * @var string
     */
    protected $orderState = OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED;

    /**
     * @var \ArrayIterator
     */
    protected $list;

    /**
     * @var string
     */
    protected $itemClassName;


    /**
     * @return string
     */
    public function getItemClassName()
    {
        return $this->itemClassName;
    }


    /**
     * @param string $className
     *
     * @return $this
     */
    public function setItemClassName($className)
    {
        $this->itemClassName = $className;
        return $this;
    }


    /**
     * @param array $row
     *
     * @return IOrderListItem
     */
    protected function createResultItem(array $row)
    {
        $class = $this->getItemClassName();
        return new $class($row);
    }


    /**
     * @param string $type
     *
     * @return IOrderList
     */
    public function setListType($type)
    {
        $this->listType = $type;
        return $this;
    }


    /**
     * @return string
     */
    public function getListType()
    {
        return $this->listType;
    }

    /**
     * @return string
     */
    public function getOrderState()
    {
        return $this->orderState;
    }

    /**
     * @param string $orderState
     *
     * @return $this
     */
    public function setOrderState($orderState)
    {
        $this->orderState = $orderState;
        return $this;
    }


    /**
     * @return IOrderListItem[]
     */
    public function load()
    {
        if($this->list === null)
        {
            // load
            $conn = Resource::getConnection();

            $this->list = new \ArrayIterator($conn->fetchAll( $this->getQuery() ));
            $this->rowCount = (int)$conn->fetchCol( 'SELECT FOUND_ROWS() as "cnt"')[0];
        }

        return $this;
    }


    /**
     * Return a fully configured Paginator Adapter from this method.
     *
     * @return Zend_Paginator_Adapter_Interface
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }


    /**
     * Returns an collection of items for a page.
     *
     * @param  integer $offset           Page offset
     * @param  integer $itemCountPerPage Number of items per page
     *
     * @return IOrderListItem[]
     */
    public function getItems($offset, $itemCountPerPage)
    {
        // load
        return $this
            ->setLimit( $itemCountPerPage, $offset )
            ->load();
    }


    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param $limit
     *
     * @return $this
     */
    public function setLimit($limit, $offset = 0)
    {
        $this->limit = (int)$limit;
        $this->offset = (int)$offset;
        $this->list = null;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        $this->load();
        if($this->count() > 0)
        {
            return $this->createResultItem( $this->list->current() );
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->load();
        $this->list->next();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        $this->load();
        return $this->list->key();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid()
    {
        $this->load();
        return $this->list->valid();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->load();
        $this->list->rewind();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Seeks to a position
     *
     * @link http://php.net/manual/en/seekableiterator.seek.php
     *
     * @param int $position <p>
     *                      The position to seek to.
     *                      </p>
     *
     * @return void
     */
    public function seek($position)
    {
        $this->load();
        $this->list->seek( $position );
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *       </p>
     *       <p>
     *       The return value is cast to an integer.
     */
    public function count()
    {
        $this->load();
        return $this->rowCount;
    }
}