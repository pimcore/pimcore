<?php

$routes = new \Pimcore\Model\Staticroute\Listing();
$routes = $routes->load();
/** @var  $route \Pimcore\Model\Staticroute */
foreach ($routes as $route) {
    $siteId = $route->getSiteId();
    if ($siteId && !is_array($siteId)) {
        $route->setSiteId(array($siteId));
    }
    $route->save();
}
