<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_ConfigController extends Pimcore\Controller\Action\Admin
{

    public function jsConfigAction() {

        $this->disableViewAutoRender();

        $config = \OnlineShop\Framework\Factory::getInstance()->getConfig();

        $params = [];

        foreach ($config->onlineshop->pimcore as $confName => $conf) {
            $entries = [];
            foreach($conf as $entryName => $entry) {
                $entries[$entryName] = $entry->toArray();
            }
            $params[$confName] = $entries;
        }

        $javascript="pimcore.registerNS(\"pimcore.plugin.OnlineShop.plugin.config\");";

        $javascript.= "pimcore.plugin.OnlineShop.plugin.config = ";
        $javascript.= json_encode($params).";";

        echo $javascript;
    }
}
