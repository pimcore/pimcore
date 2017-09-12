<?php

declare(strict_types=1);

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

namespace Pimcore\Install\Controller;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Pimcore\Db\Connection;
use Pimcore\Install\Installer;
use Pimcore\Install\Profile\Profile;
use Pimcore\Install\Profile\ProfileLocator;
use Pimcore\Tool\Requirements;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function indexAction(Installer $installer, ProfileLocator $profileLocator)
    {
        $profiles = array_map(function (Profile $profile) {
            return [
                $profile->getId(),
                $profile->getName()
            ];
        }, array_values($profileLocator->getProfiles()));

        return $this->render('@install/install.html.twig', [
            'errors'   => $installer->checkPrerequisites(),
            'profiles' => $profiles
        ]);
    }

    public function installAction(Request $request, Installer $installer)
    {
        $errors = $installer->install($request->request->all());

        if (count($errors) === 0) {
            return $this->json([
                'success' => true
            ]);
        } else {
            return $this->json([
                'success' => false,
                'errors'  => $errors
            ], 400);
        }
    }

    public function checkAction(Request $request)
    {
        $checksPHP  = Requirements::checkPhp();
        $checksFS   = Requirements::checkFilesystem();
        $checksApps = Requirements::checkExternalApplications();

        $db = $this->buildDatabaseConnection($request);

        if ($db) {
            $checksMySQL = Requirements::checkMysql($db);
        } else {
            return new Response('Not possible... no or wrong database settings given.<br />Please fill out the MySQL Settings in the install form an click again on `Check Requirements´');
        }

        $viewParams = [
            'checksApps'  => $checksApps,
            'checksPHP'   => $checksPHP,
            'checksMySQL' => $checksMySQL,
            'checksFS'    => $checksFS,
            'headless'    => (bool)$request->get('headless')
        ];

        return $this->render('@PimcoreAdminBundle/Admin/Install/check.html.twig', $viewParams);
    }

    /**
     * @param Request $request
     *
     * @return Connection|null
     */
    private function buildDatabaseConnection(Request $request)
    {
        try {
            $dbConfig = [
                'user'         => $request->get('mysql_username'),
                'password'     => $request->get('mysql_password'),
                'dbname'       => $request->get('mysql_database'),
                'driver'       => 'pdo_mysql',
                'wrapperClass' => Connection::class,
            ];

            $hostSocketValue = $request->get('mysql_host_socket');
            if (!empty($hostSocketValue) && file_exists($hostSocketValue)) {
                $dbConfig['unix_socket'] = $hostSocketValue;
            } else {
                $dbConfig['host'] = $hostSocketValue;
                $dbConfig['port'] = $request->get('mysql_port');
            }

            $config = new Configuration();

            /** @var Connection $db */
            $db = DriverManager::getConnection($dbConfig, $config);

            // connect and validate connection
            $db->connect();
            if ($db->isConnected()) {
                return $db;
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
