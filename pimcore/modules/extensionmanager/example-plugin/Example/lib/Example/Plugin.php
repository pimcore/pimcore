<?php

namespace Example;

use Pimcore\API\Plugin as PluginLib;

class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface
{
    public function init()
    {
        parent::init();

        // register your events here

        // using anonymous function
        \Pimcore::getEventManager()->attach("document.postAdd", function ($event) {
            // do something
            $document = $event->getTarget();
        });

        // using methods
        \Pimcore::getEventManager()->attach("document.postUpdate", [$this, "handleDocument"]);

        // for more information regarding events, please visit:
        // https://www.pimcore.org/docs/latest/Extending_Pimcore/Event_API_and_Event_Manager.html
        // http://framework.zend.com/manual/1.12/de/zend.event-manager.event-manager.html
        // https://www.pimcore.org/docs/latest/Extending_Pimcore/Plugin_Developers_Guide/Plugin_Class.html
    }

    public function handleDocument($event)
    {
        // do something
        $document = $event->getTarget();
    }

    public static function install()
    {
        // implement your own logic here
        return true;
    }

    public static function uninstall()
    {
        // implement your own logic here
        return true;
    }

    public static function isInstalled()
    {
        // implement your own logic here
        return true;
    }
}
