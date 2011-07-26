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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_LoginController extends Pimcore_Controller_Action_Admin {

    public function init() {

        parent::init();
        $this->protect();
    }


    public function lostpasswordAction() {


        $username = $this->_getParam("username");
        if ($username) {
            $user = User::getByName($username);
            if (!$user instanceof User) {
                $this->view->error = "user unknown";
            } else {
                if ($user->isActive()) {
                    if ($user->getEmail()) {
                        $token = Pimcore_Tool_Authentication::generateToken($username, $user->getPassword(), MCRYPT_TRIPLEDES, MCRYPT_MODE_ECB);
                        $protocol = "http://";
                        if(strpos(strtolower($_SERVER["SERVER_PROTOCOL"]),"https")===0){
                            $protocol = "https://";
                        }
                        $uri = $protocol.$_SERVER['SERVER_NAME'];
                        $loginUrl = $uri . "/admin/login/login/?username=" . $username . "&token=" . $token;
                        
                        try {
                            
                            $mail = Pimcore_Tool::getMail(array($user->getEmail()), "Pimcore lost password service");
                            $mail->setBodyText("Login to pimcore and change your password using the following link. This temporary login link will expire in 30 minutes: \r\n\r\n" . $loginUrl);
                            $mail->send();
                            $this->view->success = true;
                        } catch (Exception $e) {
                            $this->view->error = "could not send email";
                        }

                    } else {
                        $this->view->error = "user has no email address";
                    }
                } else {
                    $this->view->error = "user inactive";
                }

            }
        }


    }


    public function indexAction() {

        if ($this->getUser() instanceof User) {
            $this->_redirect("/admin/?_dc=" . time());
        }

        if ($this->_getParam("auth_failed")) {
            if ($this->_getParam("inactive")) {
                $this->view->error = "error_user_inactive";
            } else {
                $this->view->error = "error_auth_failed";
            }
        }
        if ($this->_getParam("session_expired")) {
            $this->view->error = "error_session_expired";
        }
    }

    public function loginAction() {

        $userInactive = false;
        try {
            $user = User::getByName($this->_getParam("username"));

            if ($user instanceof User) {
                if ($user->isActive()) {
                    $authenticated = false;

                    if ($user->getPassword() == Pimcore_Tool_Authentication::getPasswordHash($this->_getParam("username"), $this->_getParam("password"))) {
                        $authenticated = true;

                    } else if ($this->_getParam("token") and Pimcore_Tool_Authentication::tokenAuthentication($this->_getParam("username"), $this->_getParam("token"), MCRYPT_TRIPLEDES, MCRYPT_MODE_ECB, false)) {
                        $authenticated = true;
                    }
                    else {
                        throw new Exception("User and Password doesn't match");
                    }

                    if ($authenticated) {
                        $adminSession = new Zend_Session_Namespace("pimcore_admin");
                        $adminSession->user = $user;
                        $adminSession->frozenuser = $user->getAsFrozen();
                    }

                } else {
                    $userInactive = true;
                    throw new Exception("User is inactive");

                }

            }
            else {
                throw new Exception("User doesn't exist");
            }
        } catch (Exception $e) {

            //see if module ore plugin authenticates user
            $user = Pimcore_API_Plugin_Broker::getInstance()->authenticateUser($this->_getParam("username"),$this->_getParam("password"));
            if($user instanceof User){
                $adminSession = new Zend_Session_Namespace("pimcore_admin");
                $adminSession->user = $user;
                $adminSession->frozenuser = $user->getAsFrozen();
                $this->_redirect("/admin/?_dc=" . time());
            } else {
                $this->writeLogFile($this->_getParam("username"), $e->getMessage());
                Logger::info("Login Exception" . $e);

                $this->_redirect("/admin/login/?auth_failed=true&inactive=" . $userInactive);
                $this->getResponse()->sendResponse();
                exit;
            }
        }

        $this->_redirect("/admin/?_dc=" . time());
    }

    public function logoutAction() {
        $adminSession = new Zend_Session_Namespace("pimcore_admin");

        if ($adminSession->user instanceof User) {
            Pimcore_API_Plugin_Broker::getInstance()->preLogoutUser($adminSession->user);
            $adminSession->user = null;
            $adminSession->frozenuser = null;
        }

        setcookie("pimcore_admin_sid", "", time() - 3600);
        unset($_COOKIE['pimcore_admin_sid']);

        $this->_redirect("/admin/login/");
    }


    /**
     * Protection against bruteforce
     */

    protected function getLogFile() {

        $logfile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/loginerror.log";

        if (!is_file($logfile)) {
            file_put_contents($logfile, "");
            chmod($logfile, 0766);
        }

        if (!is_writable($logfile)) {
            $m = "It seems that " . $logfile . " is not writable.";
            Logger::crit($m);
            die($m);
        }


        return file_get_contents($logfile);
    }

    protected function protect() {

        $data = $this->readLogFile();

        $matches = 0;

        foreach ($data as $login) {
            if ($login[1] == $this->getRemoteHost()) {
                if ($login[0] > (time() - 300)) {
                    $matches++;
                }
            }
        }

        if ($matches > 4) {
            $m = "Security Alert: Too much logins, please wait 5 minutes and try again.";
            logger::crit($m);
            die($m);
        }
    }

    protected function readLogFile() {

        $data = $this->getLogFile();
        $lines = explode("\n", $data);
        $entries = array();

        if (is_array($lines) && count($lines) > 0) {
            foreach ($lines as $line) {
                $entries[] = explode(",", $line);
            }
        }

        return $entries;
    }

    protected function writeLogFile($username, $error) {

        $logfile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/loginerror.log";
        $data = $this->readLogFile();

        $remoteHost = $this->getRemoteHost();

        $data[] = array(
            time(),
            $remoteHost,
            $username
        );

        $lines = array();


        foreach ($data as $item) {
            $lines[] = implode(",", $item);
        }

        // only save 2000 entries
        $maxEntries = 2000;
        if (count($lines) > $maxEntries) {
            $lines = array_splice($lines, $maxEntries * -1);
        }

        file_put_contents($logfile, implode("\n", $lines));
        chmod($logfile, 0766);
    }

    protected function getRemoteHost() {
        $remoteHost = $_SERVER["REMOTE_ADDR"];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $remoteHost = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $remoteHost;
    }
}
