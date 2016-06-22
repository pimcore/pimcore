<?php
/**
 * This file is called directly after the pimcore startup (/pimcore/config/startup.php)
 * Here you can do some modifications before the dispatch process begins, this includes some Zend Framework plugins
 * or some other things which should be done before the initialization of pimcore is completed, below are some examples.
 * IMPORTANT: Please rename this file to startup.php to use it!
 */

/*
// register a custom ZF controller plugin
$front = \Zend_Controller_Front::getInstance();
$front->registerPlugin(new Website\Controller\Plugin\Custom(), 700);
*/

/*
// register a custom ZF route
$router = $front->getRouter();
$routeCustom = new \Zend_Controller_Router_Route(
    'custom/:controller/:action/*',
    array(
        'module' => 'custom',
        "controller" => "index",
        "action" => "index"
    )
);
$router->addRoute('custom', $routeCustom);
$front->setRouter($router);
*/

/*
// add a custom module directory
$front = \Zend_Controller_Front::getInstance();
$front->addModuleDirectory(PIMCORE_DOCUMENT_ROOT . "/customModuleDirectory");

// add some custom events
\Pimcore::getEventManager()->attach("object.postUpdate", function (\Zend_EventManager_Event $event) {
    $object = $event->getTarget();
    // do something ...
    // get a parameter from the event
    $saveVersionOnly = $event->getParam("saveVersionOnly");
    // ...
});

// do some dependency injection magic
\Pimcore::getDiContainer()->set("foo", "bar");

*/
