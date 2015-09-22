<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Search\Backend;

class Module extends \Pimcore\API\Module\AbstractModule {

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function init() {

        // attach event-listener
        foreach (["asset","object","document"] as $type) {
            \Pimcore::getEventManager()->attach($type . ".postAdd", array($this, "postAddElement"));
            \Pimcore::getEventManager()->attach($type . ".postUpdate", array($this, "postUpdateElement"));
            \Pimcore::getEventManager()->attach($type . ".preDelete", array($this, "preDeleteElement"));
        }

    }

    /**
     * @param $e
     */
    public function postAddElement($e){
        $searchEntry = new Data($e->getTarget());
        $searchEntry->save();

    }

    /**
     * @param $e
     */
    public function preDeleteElement($e){
        
        $searchEntry = Data::getForElement($e->getTarget());
        if($searchEntry instanceof Data and $searchEntry->getId() instanceof Data\Id){
            $searchEntry->delete();
        }

    }

    /**
     * @param $e
     */
    public function postUpdateElement($e){
        $element = $e->getTarget();
        $searchEntry = Data::getForElement($element);
        if($searchEntry instanceof Data and $searchEntry->getId() instanceof Data\Id ){
            $searchEntry->setDataFromElement($element);
            $searchEntry->save();
        } else {
            $this->postAddElement($e);
        }
    }
}
