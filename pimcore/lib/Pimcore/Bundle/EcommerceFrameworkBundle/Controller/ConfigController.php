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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigController
 *
 * @Route("/config")
 */
class ConfigController extends AdminController
{
    /**
     * @Route("/js-config")
     *
     * @return string
     */
    public function jsConfigAction()
    {
        $config = $this->getParameter('pimcore_ecommerce.pimcore.config');

        $orderList = $config['menu']['order_list'];
        if (isset($orderList['route']) && !empty($orderList['route'])) {
            $orderList['route'] = $this->get('router')->generate($orderList['route']);
        } elseif (isset($orderList['path']) && !empty($orderList['path'])) {
            $orderList['route'] = $orderList['path'];
        }

        if (array_key_exists('path', $orderList)) {
            unset($orderList['path']);
        }

        $config['menu']['order_list'] = $orderList;

        $javascript = 'pimcore.registerNS("pimcore.bundle.EcommerceFramework.bundle.config");' . PHP_EOL;

        $javascript .= 'pimcore.bundle.EcommerceFramework.bundle.config = ';
        $javascript .= json_encode($config) . ';';

        $response = new Response($javascript);
        $response->headers->set('Content-Type', 'application/javascript');

        return $response;
    }
}
