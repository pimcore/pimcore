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

namespace PimcoreLegacyBundle\Zend\View;

use Pimcore\Bundle\PimcoreBundle\Service\Request\TemplateVarsResolver;
use Pimcore\View;

class ViewHelperBridge
{
    /**
     * @var ViewProvider
     */
    protected $viewProvider;

    /**
     * @var TemplateVarsResolver
     */
    protected $varsResolver;

    /**
     * @var View
     */
    protected $defaultView;

    /**
     * @param ViewProvider $viewProvider
     * @param TemplateVarsResolver $varsResolver
     */
    public function __construct(ViewProvider $viewProvider, TemplateVarsResolver $varsResolver)
    {
        $this->viewProvider = $viewProvider;
        $this->varsResolver = $varsResolver;
    }

    /**
     * @return View
     */
    protected function getDefaultView()
    {
        if (null === $this->defaultView) {
            $this->defaultView = $this->createView();
        }

        return $this->defaultView;
    }

    /**
     * @return View
     */
    protected function createView()
    {
        return $this->viewProvider->createView($this->varsResolver->getTemplateVars());
    }

    /**
     * Test if a view helper exists
     *
     * @param string $name
     * @return bool
     */
    public function hasHelper($name)
    {
        try {
            $helper = $this->getDefaultView()->getHelper($name);

            return $helper instanceof \Zend_View_Helper_Interface;
        } catch (\Zend_Loader_PluginLoader_Exception $e) {
            // noop
        }

        return false;
    }

    /**
     * Get a view helper
     *
     * @param string $name
     * @return \Zend_View_Helper_Interface|object
     */
    public function getHelper($name)
    {
        return $this->getDefaultView()->getHelper($name);
    }

    /**
     * Execute a Zend View helper with the given arguments
     *
     * @param $helperName
     * @param array $arguments
     * @return mixed
     */
    public function execute($helperName, array $arguments = [])
    {
        $view = $this->viewProvider->createView();

        // set global variables (document, editmode) on the new view
        foreach ($this->varsResolver->getTemplateVars() as $key => $value) {
            $view->$key = $value;
        }

        return call_user_func_array([$view, $helperName], $arguments);
    }
}
