<?php

/**
 * Interface for checkout step implementations of online shop framework
 */
interface OnlineShop_Framework_ICheckoutStep {

    /**
     * @return string
     */
    public function getName();

    /**
     * returns saved data of step
     *
     * @return mixed
     */
    public function getData();

    /**
     * sets delivered data and commits step
     *
     * @param  $data
     * @return bool
     */
    public function commit($data);

}
