<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 28.10.11
 * Time: 14:57
 * To change this template use File | Settings | File Templates.
 */
 
interface OnlineShop_Framework_IAvailability{
    /**
     * @abstract
     * @return boolean
     */
    public function getAvailable();

}