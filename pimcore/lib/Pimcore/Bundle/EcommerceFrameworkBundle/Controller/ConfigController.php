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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Config\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigController
 * @Route("/config")
 */
class ConfigController extends AdminController
{

    /**
     * @Route("/js-config")
     * @return string
     */
    public function jsConfigAction()
    {
        $config = Factory::getInstance()->getConfig();

        $params = [];

        if ($config->ecommerceframework->pimcore instanceof Config) {
            foreach ($config->ecommerceframework->pimcore as $confName => $conf) {
                $entries = [];
                foreach ($conf as $entryName => $entry) {
                    $entries[$entryName] = $entry->toArray();
                }
                $params[$confName] = $entries;
            }
        }

        $javascript="pimcore.registerNS(\"pimcore.bundle.EcommerceFramework.bundle.config\");";

        $javascript.= "pimcore.bundle.EcommerceFramework.bundle.config = ";
        $javascript.= json_encode($params).";";

        $response = new Response($javascript);
        $response->headers->set('Content-Type', 'application/javascript');

        return $response;
    }
}
