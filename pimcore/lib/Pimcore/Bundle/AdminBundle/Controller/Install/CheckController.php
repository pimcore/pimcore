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

namespace Pimcore\Bundle\AdminBundle\Controller\Install;

use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\User;
use Pimcore\Tool\Requirements;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class CheckController extends Controller implements EventedControllerInterface
{
    /**
     * @Route("/check")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $checksPHP = Requirements::checkPhp();
        $checksFS = Requirements::checkFilesystem();
        $checksApps = Requirements::checkExternalApplications();

        $db = null;

        if ($request->get('mysql_username')) {
            // this is before installing
            try {
                $dbConfig = [
                    'user' => $request->get('mysql_username'),
                    'password' => $request->get('mysql_password'),
                    'dbname' => $request->get('mysql_database'),
                    'driver' => 'pdo_mysql',
                    'wrapperClass' => 'Pimcore\Db\Connection',
                ];

                $hostSocketValue = $request->get('mysql_host_socket');
                if (file_exists($hostSocketValue)) {
                    $dbConfig['unix_socket'] = $hostSocketValue;
                } else {
                    $dbConfig['host'] = $hostSocketValue;
                    $dbConfig['port'] = $request->get('mysql_port');
                }

                $config = new \Doctrine\DBAL\Configuration();
                $db = \Doctrine\DBAL\DriverManager::getConnection($dbConfig, $config);
            } catch (\Exception $e) {
                $db = null;
            }
        } else {
            // this is after installing, eg. after a migration, ...
            $db = $this->get('database_connection');
        }

        if ($db) {
            $checksMySQL = Requirements::checkMysql($db);
        } else {
            return new Response('Not possible... no or wrong database settings given.<br />Please fill out the MySQL Settings in the install form an click again on `Check Requirements´');
        }

        $viewParams = [
            'checksApps' => $checksApps,
            'checksPHP'  => $checksPHP,
            'checksMySQL' => $checksMySQL,
            'checksFS' => $checksFS
        ];

        return $this->render('PimcoreAdminBundle:Install/Check:index.html.php', $viewParams);
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @return Response|void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $request = $event->getRequest();

        if (is_file(\Pimcore\Config::locateConfigFile('system.php'))) {
            // session authentication, only possible if user is logged in
            $user = \Pimcore\Tool\Authentication::authenticateSession();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException("Authentication failed!<br />If you don't have access to the admin interface any more, and you want to find out if the server configuration matches the requirements you have to rename the the system.php for the time of the check.");
            }
        } elseif ($request->get('mysql_username')) {
        } else {
            throw new AccessDeniedHttpException('Not possible... no database settings given.<br />Parameters: mysql_host,mysql_username,mysql_password,mysql_database');
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
