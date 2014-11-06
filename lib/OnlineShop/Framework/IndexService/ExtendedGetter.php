<?php
/**
 * Class OnlineShop_Framework_IndexService_ExtendedGetter
 *
 * Interface for getter of product index colums which consider sub object ids and tenant configs
 */
interface OnlineShop_Framework_IndexService_ExtendedGetter {

    public static function get($object, $config = null, $subObjectId = null, OnlineShop_Framework_IndexService_Tenant_IConfig $tenantConfig = null);
}
