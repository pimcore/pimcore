<?php

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
     * commits step and sets delivered data
     * @abstract
     * @param  $data
     * @return bool
     */
    public function commit($data);

}
