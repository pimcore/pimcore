<?php

/**
 * Interface for checkout step implementations of online shop framework
 */
interface OnlineShop_Framework_ICheckoutStep {

    /**
     * @abstract
     * @return string
     */
    public function getName();

    /**
     * @abstract
     * @return mixed
     */
    public function getData();

    /**
     * sets delivered data and commits step
     *
     * @abstract
     * @param  $data
     * @return bool
     */
    public function commit($data);

}
