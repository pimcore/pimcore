<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 07.04.14
 * Time: 16:59
 */

namespace OnlineShop\Framework\Impl\OrderManager;


/**
 * Class AbstractListItem
 * template method pattern
 */
abstract class AbstractOrderListItem
{
    /**
     * @var array
     */
    protected $resultRow;


    /**
     * @param array $resultRow
     */
    public function __construct(array $resultRow)
    {
        $this->resultRow = $resultRow;
    }


    /**
     * @return int
     */
    abstract function getId();
}