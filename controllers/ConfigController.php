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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


class EcommerceFramework_ConfigController extends Pimcore\Controller\Action\Admin
{

    public function jsConfigAction() {

        $this->disableViewAutoRender();

        $config = \OnlineShop\Framework\Factory::getInstance()->getConfig();

        $params = [];

        if ($config->onlineshop->pimcore instanceof \Zend_Config) {
            foreach ($config->onlineshop->pimcore as $confName => $conf) {
                $entries = [];
                foreach($conf as $entryName => $entry) {
                    $entries[$entryName] = $entry->toArray();
                }
                $params[$confName] = $entries;
            }
        }

        $javascript="pimcore.registerNS(\"pimcore.plugin.OnlineShop.plugin.config\");";

        $javascript.= "pimcore.plugin.OnlineShop.plugin.config = ";
        $javascript.= json_encode($params).";";

        header('Content-Type: application/javascript');
        echo $javascript;
        exit;
    }
}
