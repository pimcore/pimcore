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

        // IE compatibility
        $this->getResponse()->setHeader("X-UA-Compatible", "IE=8; IE=9", true);
    }


    public function lostpasswordAction() {


        $username = $this->getParam("username");
        if ($username) {
            $user = User::getByName($username);
            if (!$user instanceof User) {
                $this->view->error = "user unknown";
            } else {
                if ($user->isActive()) {
                    if ($user->getEmail()) {
                        $token = Pimcore_Tool_Authentication::generateToken($username, $user->getPassword(), MCRYPT_TRIPLEDES, MCRYPT_MODE_ECB);
                        $uri = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() ;
                        $loginUrl = $uri . "/admin/login/login/?username=" . $username . "&token=" . $token . "&reset=true";
                        
                        try {
                            
                            $mail = Pimcore_Tool::getMail(array($user->getEmail()), "Pimcore lost password service");
                            $mail->setIgnoreDebugMode(true);
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
            $this->redirect("/admin/?_dc=" . time());
        }

        if ($this->getParam("auth_failed")) {
            if ($this->getParam("inactive")) {
                $this->view->error = "error_user_inactive";
            } else {
                $this->view->error = "error_auth_failed";
            }
        }
        if ($this->getParam("session_expired")) {
            $this->view->error = "error_session_expired";
        }
    }

    public function deeplinkAction () {
        // check for deeplink
        if($_SERVER["QUERY_STRING"]) {
            $this->view->tab = $_SERVER["QUERY_STRING"];
        }
    }

    public function loginAction() {

        $userInactive = false;
        try {
            $user = User::getByName($this->getParam("username"));

            if ($user instanceof User) {
                if ($user->isActive()) {
                    $authenticated = false;

                    if ($user->getPassword() == Pimcore_Tool_Authentication::getPasswordHash($this->getParam("username"), $this->getParam("password"))) {
                        $authenticated = true;

                    } else if ($this->getParam("token") and Pimcore_Tool_Authentication::tokenAuthentication($this->getParam("username"), $this->getParam("token"), MCRYPT_TRIPLEDES, MCRYPT_MODE_ECB, false)) {
                        $authenticated = true;

                        // save the information to session when the user want's to reset the password
                        // this is because otherwise the old password is required => see also PIMCORE-1468
                        if($this->getParam("reset")) {
                            $adminSession = Pimcore_Tool_Authentication::getSession();
                            $adminSession->password_reset = true;
                        }
                    }
                    else {
                        throw new Exception("User and Password doesn't match");
                    }

                    if ($authenticated) {
                        $adminSession = Pimcore_Tool_Authentication::getSession();
                        $adminSession->user = $user;

                        Zend_Session::regenerateId();
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

            //see if module or plugin authenticates user
            $user = Pimcore_API_Plugin_Broker::getInstance()->authenticateUser($this->getParam("username"),$this->getParam("password"));
            if($user instanceof User){
                $adminSession = Pimcore_Tool_Authentication::getSession();
                $adminSession->user = $user;
                $this->redirect("/admin/?_dc=" . time());
            } else {
                $this->writeLogFile($this->getParam("username"), $e->getMessage());
                Logger::info("Login Exception" . $e);

                $this->redirect("/admin/login/?auth_failed=true&inactive=" . $userInactive);
                exit;
            }
        }

        $this->redirect("/admin/?_dc=" . time());
    }

    public function logoutAction() {
        $adminSession = Pimcore_Tool_Authentication::getSession();

        if ($adminSession->user instanceof User) {
            Pimcore_API_Plugin_Broker::getInstance()->preLogoutUser($adminSession->user);
            $adminSession->user = null;
        }

        Zend_Session::destroy();

        // cleanup pimcore-cookies => 315554400 => strtotime('1980-01-01')
        setcookie("pimcore_opentabs", false, 315554400, "/");

        $this->redirect("/admin/login/");
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

        $user = $this->getParam("username");
        $data = $this->readLogFile();

        $matchesIpOnly = 0;
        $matchesUserOnly = 0;
        $matchesUserIp = 0;

        foreach ($data as $login) {
            $matchIp = false;
            $matchUser = false;

            if ($login[0] > (time() - 300)) {
                if($user && $login[2] == $user) {
                    $matchesUserOnly++;
                    $matchUser = true;
                }
                if ($login[1] == Pimcore_Tool::getAnonymizedClientIp()) {
                    $matchesIpOnly++;
                    $matchIp = true;
                }

                if($matchIp && $matchUser) {
                    $matchesUserIp++;
                }
            }
        }

        if ($matchesIpOnly > 49 || $matchesUserOnly > 9 || $matchesUserIp > 4) {
            $m = "Security Alert: Too many login attempts , please wait 5 minutes and try again.";
            Logger::crit($m);
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

        $remoteHost = Pimcore_Tool::getAnonymizedClientIp();

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
}

