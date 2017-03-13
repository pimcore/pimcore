<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Action;

use Pimcore\Controller\Action;
use Pimcore\Tool\Authentication;
use Pimcore\Config;
use Pimcore\Model\User;

class Webservice extends Action
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        $conf = Config::getSystemConfig();
        if (!$conf->webservice->enabled) {
            throw new \Exception("Webservice API isn't enabled");
        }

        if (!$this->getParam("apikey") && $_COOKIE["pimcore_admin_sid"]) {
            $user = Authentication::authenticateSession();
            if (!$user instanceof User) {
                throw new \Exception("User is not valid");
            }
        } elseif (!$this->getParam("apikey")) {
            throw new \Exception("API key missing");
        } else {
            $apikey = $this->getParam("apikey");

            $userList = new User\Listing();
            $userList->setCondition("apiKey = ? AND type = ? AND active = 1", [$apikey, "user"]);
            $users = $userList->load();

            if (!is_array($users) or count($users)!==1) {
                throw new \Exception("API key error.");
            }

            if (!$users[0]->getApiKey()) {
                throw new \Exception("Couldn't get API key for user.");
            }

            $user = $users[0];
        }

        \Zend_Registry::set("pimcore_admin_user", $user);

        parent::init();
    }
}
