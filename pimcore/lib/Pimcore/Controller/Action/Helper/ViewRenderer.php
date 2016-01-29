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

namespace Pimcore\Controller\Action\Helper;

use Pimcore\Controller\Action\Frontend as FrontendController;
use Pimcore\Tool;
use Pimcore\View;

class ViewRenderer extends \Zend_Controller_Action_Helper_ViewRenderer
{

    /**
     * @var bool
     */
    public $isInitialized = false;

    /**
     *
     */
    public function postDispatch()
    {
        if ($this->_shouldRender()) {
            if (method_exists($this->getActionController(), "getRenderScript")) {
                if ($script = $this->getActionController()->getRenderScript()) {
                    $this->renderScript($script);
                }
            }
        }
        
        parent::postDispatch();
    }

    /**
     * @param null $path
     * @param null $prefix
     * @param array $options
     */
    public function initView($path = null, $prefix = null, array $options = array())
    {
        if (null === $this->view) {
            $view = new View();
            $view->setRequest($this->getRequest());
            $view->addHelperPath(PIMCORE_PATH . "/lib/Pimcore/View/Helper", "\\Pimcore\\View\\Helper\\");

            $this->setView($view);
        }

        parent::initView($path, $prefix, $options);


        $this->setViewSuffix(View::getViewScriptSuffix());

        // this is very important, the initView could be called multiple times.
        // if we add the path on every call, we have big performance issues.
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        $paths = $this->view->getScriptPaths();
        // script pathes for layout path
        foreach (array_reverse($paths) as $path) {
            $path = str_replace("\\", "/", $path);
            if (!in_array($path, $paths)) {
                $this->view->addScriptPath($path);
            }

            $path = str_replace("/scripts", "/layouts", $path);
            if (!in_array($path, $paths)) {
                $this->view->addScriptPath($path);
            }
        }
    }

    /**
     * @param $isInitialized
     * @return $this
     */
    public function setIsInitialized($isInitialized)
    {
        $this->isInitialized = $isInitialized;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsInitialized()
    {
        return $this->isInitialized;
    }
}

// unfortunately we need this alias here, since ZF plugin loader isn't able to handle namespaces correctly
class_alias("Pimcore\\Controller\\Action\\Helper\\ViewRenderer", "Pimcore_Controller_Action_Helper_ViewRenderer");
