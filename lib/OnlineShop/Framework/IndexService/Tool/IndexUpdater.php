<?php

class OnlineShop_Framework_IndexService_Tool_IndexUpdater {

    /**
     * Runs update index for all tenants
     *  - but does not run processPreparationQueue or processUpdateIndexQueue
     *
     * @param $objectListClass
     * @param string $condition
     * @param bool $updateIndexStructures
     * @param string $loggername
     */
    public static function updateIndex($objectListClass, $condition = "", $updateIndexStructures = false, $loggername = "indexupdater") {
        $updater = OnlineShop_Framework_Factory::getInstance()->getIndexService();
        if($updateIndexStructures) {
            \Pimcore\Model\Cache::clearTag("ecommerceconfig");
            $updater->createOrUpdateIndexStructures();
        }

        $page = 0;
        $pageSize = 100;
        $count = $pageSize;

        while($count > 0) {

            self::log($loggername, "=========================");
            self::log($loggername, "Update Index Page: " . $page);
            self::log($loggername, "=========================");

            $products = new $objectListClass();
            $products->setUnpublished(true);
            $products->setOffset($page * $pageSize);
            $products->setLimit($pageSize);
            $products->setObjectTypes(array("object", "folder", "variant"));
            $products->setIgnoreLocalizedFields(true);
            $products->setCondition($condition);

            foreach($products as $p) {
                self::log($loggername, "Updating product " . $p->getId());
                $updater->updateIndex($p);
            }
            $page++;

            $count = count($products->getObjects());

            Pimcore::collectGarbage();
        }
    }


    /**
     * Runs processPreparationQueue for given tenants or for all tenants
     *
     * @param array $tenants
     * @param int $maxRounds - max rounds after process returns. null for infinite run until no work is left
     * @param string $loggername
     *
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public static function processPreparationQueue($tenants = null, $maxRounds = null, $loggername = "indexupdater") {
        if($tenants == null) {
            $tenants = OnlineShop_Framework_Factory::getInstance()->getAllTenants();
        }

        if(!is_array($tenants)) {
            $tenants = array($tenants);
        }

        foreach($tenants as $tenant) {

            self::log($loggername, "=========================");
            self::log($loggername, "Processing preparation queue for tenant: " . $tenant);
            self::log($loggername, "=========================");

            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
            $env->setCurrentAssortmentTenant($tenant);

            $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
            $worker = $indexService->getCurrentTenantWorker();

            if($worker instanceof OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker) {
                $round = 0;
                $result = true;
                while($result) {
                    $round++;
                    self::log($loggername, "Starting round: " . $round);

                    $result = $worker->processPreparationQueue();
                    self::log($loggername, "processed preparation queue elements: " . $result);

                    Pimcore::collectGarbage();

                    if($maxRounds && $maxRounds == $round) {
                        self::log($loggername, "skipping process after $round rounds.");
                        return;
                    }
                }
            }
        }
    }

    /**
     * Runs processUpdateIndexQueue for given tenants or for all tenants
     *
     * @param null $tenants
     * @param int $maxRounds - max rounds after process returns. null for infinite run until no work is left
     * @param string $loggername
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public static function processUpdateIndexQueue($tenants = null, $maxRounds = null, $loggername = "indexupdater") {
        if($tenants == null) {
            $tenants = OnlineShop_Framework_Factory::getInstance()->getAllTenants();
        }

        if(!is_array($tenants)) {
            $tenants = array($tenants);
        }

        foreach($tenants as $tenant) {

            self::log($loggername, "=========================");
            self::log($loggername, "Processing update index elements for tenant: " . $tenant);
            self::log($loggername, "=========================");

            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
            $env->setCurrentAssortmentTenant($tenant);

            $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
            $worker = $indexService->getCurrentTenantWorker();


            if($worker instanceof OnlineShop_Framework_IndexService_Tenant_IBatchProcessingWorker) {
                $result = true;
                $round = 0;
                while($result) {
                    $round++;
                    self::log($loggername, "Starting round: " . $round);

                    $result = $worker->processUpdateIndexQueue($maxRounds);
                    self::log($loggername, "processed update index elements: " . $result);

                    Pimcore::collectGarbage();

                    if($maxRounds && $maxRounds == $round) {
                        self::log($loggername, "skipping process after $round rounds.");
                        return;
                    }
                }
            }
        }
    }

    private static function log($loggername, $message) {
        \Pimcore\Log\Simple::log($loggername, $message);
        echo $message . "\n";
    }


}