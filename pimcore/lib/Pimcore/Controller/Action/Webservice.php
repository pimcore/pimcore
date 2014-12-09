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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Controller\Action;

use Pimcore\Controller\Action;
use Pimcore\Tool\Authentication;
use Pimcore\Config;
use Pimcore\Model\User;

class Webservice extends Action {

    /**
     * @throws \Exception
     */
    public function init() {

        $conf = Config::getSystemConfig();
        if(!$conf->webservice->enabled) {
            throw new \Exception("Webservice API isn't enabled");
        }

        if(!$this->getParam("apikey") && $_COOKIE["pimcore_admin_sid"]){
            $user = Authentication::authenticateSession();
            if(!$user instanceof User) {
                throw new \Exception("User is not valid");
            }
        } else if (!$this->getParam("apikey")) {
            throw new \Exception("API key missing");
        } else {
            $apikey = $this->getParam("apikey");

            $userList = new User\Listing();
            $userList->setCondition("apiKey = ? AND type = ? AND active = 1", array($apikey, "user"));
            $users = $userList->load();

            if(!is_array($users) or count($users)!==1){
                throw new \Exception("API key error.");
            }

            if(!$users[0]->getApiKey()){
                throw new \Exception("Couldn't get API key for user.");
            }

            $user = $users[0];
        }

        \Zend_Registry::set("pimcore_admin_user", $user);

        parent::init();
    }
}
