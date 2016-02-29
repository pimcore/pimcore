<?php

/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\View\Helper;

use Pimcore\Model\Document;
use Pimcore\Cache as CacheManager;
use Pimcore\Model\Site;

class PimcoreNavigation extends \Zend_View_Helper_Navigation
{
    /**
     * @var PimcoreNavigationController
     */
    public static $_controller;

    /**
     * @return PimcoreNavigationController
     */
    public static function getController()
    {
        if (!self::$_controller) {
            self::$_controller = new PimcoreNavigationController();
        }

        return self::$_controller;
    }

    /**
     * @param null $activeDocument
     * @param null $navigationRootDocument
     * @param null $htmlMenuIdPrefix
     * @param callable $pageCallback
     * @return $this|PimcoreNavigationController
     * @throws \Zend_View_Exception+
     */
    public function pimcoreNavigation($activeDocument = null, $navigationRootDocument = null, $htmlMenuIdPrefix = null, $pageCallback = null, $cache = true)
    {
        $controller = self::getController();

        if ($activeDocument) {
            // this is the new more convenient way of creating a navigation
            $navContainer = $controller->getNavigation($activeDocument, $navigationRootDocument, $htmlMenuIdPrefix, $pageCallback, $cache);
            $this->navigation($navContainer);
            $this->setUseTranslator(false);
            $this->setInjectTranslator(false);

            // now we need to refresh the container in all helpers, since the container can change from call to call
            // see also https://www.pimcore.org/issues/browse/PIMCORE-2636 which describes this problem in detail
            foreach ($this->_helpers as $helper) {
                $helper->setContainer($this->getContainer());
            }

            // just to be sure, ... load the menu helper and set the container
            $menu = $this->findHelper("menu");
            if ($menu) {
                $menu->setContainer($this->getContainer());
            }

            return $this;
        } else {
            // this is the old-style navigation
            return $controller;
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, array $arguments = array())
    {
        $return = parent::__call($method, $arguments);

        // disable the translator per default, because this isn't necessary for pimcore
        if (is_object($return) && method_exists($return, "setUseTranslator")) {
            $return->setUseTranslator(false);
        }

        return $return;
    }
}
