<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Search_Backend_Module extends Pimcore_API_Module_Abstract{

    public function init() {

        // attach event-listener
        foreach (["asset","object","document"] as $type) {
            Pimcore::getEventManager()->attach($type . ".postAdd", array($this, "postAddElement"));
            Pimcore::getEventManager()->attach($type . ".postUpdate", array($this, "postUpdateElement"));
            Pimcore::getEventManager()->attach($type . ".preDelete", array($this, "preDeleteElement"));
        }

    }

    public function postAddElement($e){
        $searchEntry = new Search_Backend_Data($e->getTarget());
        $searchEntry->save();

    }

    public function preDeleteElement($e){
        
        $searchEntry = Search_Backend_Data::getForElement($e->getTarget());
        if($searchEntry instanceof Search_Backend_Data and $searchEntry->getId() instanceof Search_Backend_Data_Id){
            $searchEntry->delete();
        }

    }

    public function postUpdateElement($e){
        $element = $e->getTarget();
        $searchEntry = Search_Backend_Data::getForElement($element);
        if($searchEntry instanceof Search_Backend_Data and $searchEntry->getId() instanceof Search_Backend_Data_Id ){
            $searchEntry->setDataFromElement($element);
            $searchEntry->save();
        } else {
            $this->postAddElement($e);
        }
    }
}
