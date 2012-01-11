<?php

class OnlineShop_Framework_PricesInfo {

    /**
       * @var OnlineShop_Framework_PriceWrapper[]
       */
      private $pricesPerQuantity = array();

    public function setPricesPerQuantity($pricesPerQuantity) {
        $this->pricesPerQuantity = $pricesPerQuantity;
    }

    public function getPricesPerQuantity() {
        return $this->pricesPerQuantity;
   }

    /**
     * @param int $quantity | null
     * @return OnlineShop_Framework_PriceWrapper
     */
    public function getPriceInfo($quantity=1){
        return $this->pricesPerQuantity[$quantity];
    }
    public function setPriceInfo(OnlineShop_Framework_PriceWrapper $priceInfo,$quantity=1){
        $this->pricesPerQuantity[$quantity]=$priceInfo;
    }
}