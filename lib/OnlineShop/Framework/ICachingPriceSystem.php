<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 11.01.12
 * Time: 11:08
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_ICachingPriceSystem extends OnlineShop_Framework_IPriceSystem {

    public function loadPriceInfos($productEntries, $options);

    public function clearPriceInfos($productEntries,$options);

}
