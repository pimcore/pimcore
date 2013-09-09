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
 
class Pimcore_Liveconnect {

    public static function setToken ($token) {
        Pimcore_Tool_Session::useSession(function($session)use ($token) {
            $session->liveconnectToken = $token;
            $session->liveconnectLastUpdate = time();
        });
    }

    public static function getToken () {
        return Pimcore_Tool_Session::useSession(function($session) {
            $timeout = 300;
            if($session->liveconnectLastUpdate < (time()-$timeout)) {
                $session->liveconnectToken = null;
            } else {
                $session->liveconnectLastUpdate = time();
            }

            return $session->liveconnectToken;
        });
    }
}