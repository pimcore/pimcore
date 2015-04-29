<?php

class OnlineShop_Framework_Impl_CartItem_List extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $cartItems;

    /**
     * @var array
     */
    protected $order = array('ASC');

    /**
     * @var array
     */
    protected $orderKey = array('`sortIndex`', '`addedDateTimestamp`');

    /**
     * @var array
     * @return boolean
     */
    public function isValidOrderKey($key) {
        if(in_array($key, ['productId', 'cartId', 'count', 'itemKey', 'addedDateTimestamp', 'sortIndex'])) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getCartItems() {
        if(empty($this->cartItems)) {
            $this->load();
        }
        return $this->cartItems;
    }

    /**
     * @param array $cartItems
     * @return void
     */
    public function setCartItems($cartItems) {
        $this->cartItems = $cartItems;
    }

    /**
     * @param string $className
     */
    public function setCartItemClassName( $className )
    {
        $this->getResource()->setClassName( $className );
    }

}
