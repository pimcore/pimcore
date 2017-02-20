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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Tool;
use Pimcore\File;
use Pimcore\Model\User;
use Pimcore\Logger;

class Admin_LoginController extends \Pimcore\Controller\Action\Admin
{
    public function init()
    {
        parent::init();
        $this->protect();
    }

    public function lostpasswordAction()
    {
        $username = $this->getParam("username");
        if ($username) {
            $user = User::getByName($username);
            if (!$user instanceof User) {
                $this->view->error = "user unknown";
            } else {
                if ($user->isActive()) {
                    if ($user->getEmail()) {
                        $token = Tool\Authentication::generateToken($username, $user->getPassword());
                        $uri = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() ;
                        $loginUrl = $uri . "/admin/login/login/?username=" . $username . "&token=" . $token . "&reset=true";

                        try {
                            $results = \Pimcore::getEventManager()->trigger("admin.login.login.lostpassword", $this, [
                                "user" => $user,
                                "loginUrl" => $loginUrl
                            ]);
                            
                            if ($results->count() === 0) { // no event has been triggered
                                $mail = Tool::getMail([$user->getEmail()], "Pimcore lost password service");
                                $mail->setIgnoreDebugMode(true);
                                $mail->setBodyText("Login to pimcore and change your password using the following link. This temporary login link will expire in 30 minutes: \r\n\r\n" . $loginUrl);
                                $mail->send();
                            }
                            $this->view->success = true;
                        } catch (\Exception $e) {
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


    public function indexAction()
    {
        $user = $this->getUser();

        if (!$user) {
            \Pimcore::getEventManager()->trigger("admin.login.index.authenticate", $this, [
                "username" => $this->getParam("username"),
                "password" => $this->getParam("password")
            ]);

            $user = $this->getUser();

            if ($user instanceof User && $user->getId() && $user->isActive() && $user->getPassword()) {
                Tool\Session::useSession(function ($adminSession) use ($user) {
                    $adminSession->user = $user;
                });
            }
        }

        if ($this->getUser() instanceof User) {
            $this->redirect("/admin/?_dc=" . time());
        }

        if ($this->getParam("auth_failed")) {
            $this->view->error = "error_auth_failed";
        }
        if ($this->getParam("session_expired")) {
            $this->view->error = "error_session_expired";
        }
    }

    public function deeplinkAction()
    {
        // check for deeplink
        $queryString = $_SERVER["QUERY_STRING"];
        if (preg_match("/(document|asset|object)_([0-9]+)_([a-z]+)/", $queryString, $deeplink)) {
            if (strpos($queryString, "token")) {
                $deeplink = $deeplink[0];
                $this->redirect("/admin/login/login?deeplink=" . $deeplink . "&" . $queryString);
            } elseif ($queryString) {
                $this->view->tab = $queryString;
            }
        }
    }

    public function loginAction()
    {
        $user = null;

        try {
            \Pimcore::getEventManager()->trigger("admin.login.login.authenticate", $this, [
                "username" => $this->getParam("username"),
                "password" => $this->getParam("password")
            ]);

            $user = $this->getUser();

            if (!$user instanceof User) {
                if ($this->getParam("password")) {
                    $user = Tool\Authentication::authenticatePlaintext($this->getParam("username"), $this->getParam("password"));
                    if (!$user) {
                        throw new \Exception("Invalid username or password");
                    }
                } elseif ($this->getParam("token")) {
                    $user = Tool\Authentication::authenticateToken($this->getParam("username"), $this->getParam("token"));

                    if (!$user) {
                        throw new \Exception("Invalid username or token");
                    }

                    // save the information to session when the user want's to reset the password
                    // this is because otherwise the old password is required => see also PIMCORE-1468
                    if ($this->getParam("reset")) {
                        Tool\Session::useSession(function ($adminSession) {
                            $adminSession->password_reset = true;
                        });
                    }
                } else {
                    throw new \Exception("Invalid authentication method, must be either password or token");
                }
            }
        } catch (\Exception $e) {

            //see if module or plugin authenticates user
            \Pimcore::getEventManager()->trigger("admin.login.login.failed", $this, [
                "username" => $this->getParam("username"),
                "password" => $this->getParam("password")
            ]);

            $user = $this->getUser();

            if (!$user instanceof User) {
                $this->writeLogFile($this->getParam("username"), $e->getMessage());
                Logger::info("Login failed: " . $e);
            }
        }

        if ($user instanceof User && $user->getId() && $user->isActive() && $user->getPassword()) {
            Tool\Session::useSession(function ($adminSession) use ($user) {
                $adminSession->user = $user;

                Tool\Session::regenerateId();
            });

            if ($this->getParam('deeplink')) {
                $this->redirect('/admin/login/deeplink/?' . $this->getParam('deeplink'));
            } else {
                $this->redirect("/admin/?_dc=" . time());
            }
        } else {
            $this->redirect("/admin/login/?auth_failed=true");
            exit;
        }
    }

    public function logoutAction()
    {
        $controller = $this;

        // clear open edit locks for this session
        \Pimcore\Model\Element\Editlock::clearSession(session_id());

        Tool\Session::useSession(function ($adminSession) use ($controller) {
            if ($adminSession->user instanceof User) {
                \Pimcore::getEventManager()->trigger("admin.login.logout", $controller, ["user" => $adminSession->user]);
                $adminSession->user = null;
            }

            \Zend_Session::destroy();
        });

        // cleanup pimcore-cookies => 315554400 => strtotime('1980-01-01')
        setcookie("pimcore_opentabs", false, 315554400, "/");

        $this->redirect("/admin/login/");
    }


    /**
     * Protection against bruteforce
     */
    protected function getLogFile()
    {
        $logfile = PIMCORE_LOG_DIRECTORY . "/loginerror.log";

        if (!is_file($logfile)) {
            File::put($logfile, "");
        }

        if (!is_writable($logfile)) {
            $m = "It seems that " . $logfile . " is not writable.";
            Logger::crit($m);
            die($m);
        }


        return file_get_contents($logfile);
    }

    protected function protect()
    {
        $user = $this->getParam("username");
        $data = $this->readLogFile();

        $matchesIpOnly = 0;
        $matchesUserOnly = 0;
        $matchesUserIp = 0;

        foreach ($data as $login) {
            $matchIp = false;
            $matchUser = false;

            $time = strtotime($login[0]);
            if ($time > (time() - 300)) {
                if ($user && $login[2] == $user) {
                    $matchesUserOnly++;
                    $matchUser = true;
                }
                if ($login[1] == Tool::getAnonymizedClientIp()) {
                    $matchesIpOnly++;
                    $matchIp = true;
                }

                if ($matchIp && $matchUser) {
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

    /**
     * @return array
     */
    protected function readLogFile()
    {
        $data = $this->getLogFile();
        $lines = explode("\n", $data);
        $entries = [];

        if (is_array($lines) && count($lines) > 0) {
            foreach ($lines as $line) {
                $entries[] = explode(",", $line);
            }
        }

        return $entries;
    }

    /**
     * @param $username
     * @param $error
     */
    protected function writeLogFile($username, $error)
    {
        $logfile = PIMCORE_LOG_DIRECTORY . "/loginerror.log";
        $data = $this->readLogFile();

        $remoteHost = Tool::getAnonymizedClientIp();

        $data[] = [
            date(\DateTime::ISO8601),
            $remoteHost,
            $username
        ];

        $lines = [];


        foreach ($data as $item) {
            $lines[] = implode(",", $item);
        }

        // only save 2000 entries
        $maxEntries = 2000;
        if (count($lines) > $maxEntries) {
            $lines = array_splice($lines, $maxEntries * -1);
        }

        File::put($logfile, implode("\n", $lines));
    }
}
